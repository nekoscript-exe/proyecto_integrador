#!/usr/bin/env python3
"""
Analizador local para Atenea.

Este programa se conecta a una base de datos MySQL/MariaDB,
obtiene informacion academica de los estudiantes, calcula
un nivel de riesgo academico y genera un reporte en formato JSON.
"""

# Permite usar anotaciones de tipos modernas
from __future__ import annotations

# Librerias utilizadas
import argparse
import csv
import json
import subprocess
import sys
from statistics import mean

# Intentar importar PyMySQL para conectarse a MySQL
try:
    import pymysql
    import pymysql.cursors as pymysql_cursors
except ImportError:
    # Si no esta instalado, se usa un plan B con mysql en consola 
    pymysql = None
    pymysql_cursors = None



def risk_score(row: dict) -> float:
    """
    Calcula una puntuacion de riesgo academico entre 0 y 100.

    La lectura es simple:
    - Base academica: promedio, materias reprobadas y asistencia.
    - Habitos: estudio, sueno y uso de redes.
    - Contexto: estres, tiempo, tareas y condiciones de estudio.
    """

    score = 0.0

    # Datos academicos base
    promedio = float(row.get("promedio") or 0)
    reprobadas = int(row.get("materias_reprobadas") or 0)
    asistencia = int(row.get("asistencia") or 0)

    # Habitos de estudio
    estudio = float(row.get("horas_estudio") or 0)
    sueno = float(row.get("horas_sueno") or 0)
    redes = float(row.get("uso_redes") or 0)

    # Bienestar y organizacion
    estres = int(row.get("nivel_estres") or 0)
    desmotivacion = int(row.get("desmotivacion") or 0)
    tiempo = int(row.get("administracion_tiempo") or 0)
    entrega = int(row.get("entrega_tareas") or 3)

    # Promedio: cuanto mas baja la nota, mayor el riesgo
    score += (
        30 if promedio < 6 else
        22 if promedio < 7 else
        14 if promedio < 7.5 else
        8 if promedio < 8 else
        4 if promedio < 8.5 else
        0
    )

    # Materias reprobadas: cada una suma presion academica real
    if reprobadas == 1:
        score += 5
    elif reprobadas == 2:
        score += 10
    elif reprobadas == 3:
        score += 15
    elif reprobadas >= 4:
        score += 20

    # Asistencia: faltar mucho suele pegar primero en el rendimiento
    score += (
        26 if asistencia < 60 else
        20 if asistencia < 75 else
        13 if asistencia < 85 else
        7 if asistencia < 90 else
        3 if asistencia < 95 else
        0
    )

    # Estudio: menos tiempo de estudio, menos recuperacion del contenido
    score += (
        8 if estudio < 1 else
        6 if estudio < 2 else
        4 if estudio < 3 else
        2 if estudio < 5 else
        0
    )

    # Sueno: dormir poco afecta memoria y concentracion
    score += (
        9 if sueno < 5 else
        6 if sueno < 5.5 else
        3 if sueno < 6.5 else
        0
    )

    # Redes: no es un demon vro, pero si roba tiempo de estudio
    score += (
        4 if redes > 6 else
        2 if redes > 4 else
        1 if redes > 2 else
        0
    )

    # Estres: cuando sube mucho, el rendimiento suele resentirse
    score += (
        12 if estres >= 10 else
        10 if estres >= 8 else
        6 if estres >= 6 else
        3 if estres >= 4 else
        0
    )

    # Desmotivacion: se nota en el ritmo de trabajo y constancia
    score += (
        5 if desmotivacion >= 4 else
        3 if desmotivacion == 3 else
        1 if desmotivacion == 2 else
        0
    )

    # Tiempo: si no hay orden, todo se acumula
    score += (
        6 if tiempo <= 1 else
        4 if tiempo == 2 else
        2 if tiempo == 3 else
        0
    )

    # Tareas: entregas bajas suelen avisar de atrasos o bloqueo
    score += (
        4 if entrega <= 1 else
        2 if entrega == 2 else
        0
    )

    # Factores extra de contexto
    score += 2 if int(row.get("acceso_internet") or 1) == 0 else 0
    score += 3 if int(row.get("espacio_estudio") or 1) == 0 else 0
    score += 2 if int(row.get("trabaja") or 0) == 1 else 0

    # Limitar el resultado a 100 puntos
    return round(min(100, score), 2)


def risk_level(score: float) -> str:
    """
    Convierte una puntuacion numerica en una categoria.
    """

    if score >= 65:
        return "Alto"

    if score >= 32:
        return "Medio"

    return "Bajo"


def connect(args: argparse.Namespace):
    if pymysql is None or pymysql_cursors is None:
        raise RuntimeError("pymysql no esta instalado")

    return pymysql.connect(
        host=args.host,
        user=args.user,
        password=args.password,
        database=args.database,
        charset="utf8mb4",
        cursorclass=pymysql_cursors.DictCursor,
    )


def fetch_with_mysql_client(args: argparse.Namespace) -> list[dict]:
    # Consulta SQL para obtener la encuesta mas reciente de cada estudiante
    query = """
        SELECT
            u.id AS usuario_id,
            u.nombre,
            u.carrera,
            e.promedio,
            e.materias_reprobadas,
            e.asistencia,
            e.horas_estudio,
            e.horas_sueno,
            e.uso_redes,
            e.actividad_fisica,
            e.entrega_tareas,
            e.tiempo_transporte,
            e.trabaja,
            e.acceso_internet,
            e.espacio_estudio,
            e.nivel_estres,
            e.desmotivacion,
            e.herramientas_digitales,
            e.administracion_tiempo
        FROM usuarios u
        INNER JOIN (
            SELECT e1.*
            FROM encuestas e1
            INNER JOIN (
                SELECT usuario_id, MAX(id) AS latest_id
                FROM encuestas
                WHERE usuario_id IS NOT NULL
                GROUP BY usuario_id
            ) latest ON latest.latest_id = e1.id
        ) e ON e.usuario_id = u.id
    """

    # Comando para ejecutar MySQL desde terminal
    command = [
        args.mysql_bin,
        "--batch",
        "--raw",
        f"--host={args.host}",
        f"--user={args.user}",
        f"--database={args.database}",
        f"--execute={query}",
    ]

    # Agregar contrasena si existe
    if args.password:
        command.insert(4, f"--password={args.password}")

    # Ejecutar el comando
    completed = subprocess.run(
        command,
        check=True,
        capture_output=True,
        text=True,
    )

    # Convertir la salida tabulada a diccionarios Python
    return list(
        csv.DictReader(
            completed.stdout.splitlines(),
            delimiter="\t"
        )
    )


def main() -> None:
    # Configuracion de argumentos de terminal
    parser = argparse.ArgumentParser(
        description="Analiza datos academicos de Atenea."
    )

    parser.add_argument("--host", default="localhost")
    parser.add_argument("--user", default="root")
    parser.add_argument("--password", default="")
    parser.add_argument("--database", default="atenea")
    parser.add_argument("--limit", type=int, default=10)
    parser.add_argument("--mysql-bin", default="/opt/lampp/bin/mysql")

    args = parser.parse_args()

    # Obtencion de datos desde la base de datos
    if pymysql is not None:
        with connect(args) as conn:
            with conn.cursor() as cursor:
                cursor.execute("""
                    SELECT
                        u.id AS usuario_id,
                        u.nombre,
                        u.carrera,
                        e.*
                    FROM usuarios u
                    INNER JOIN (
                        SELECT e1.*
                        FROM encuestas e1
                        INNER JOIN (
                            SELECT usuario_id, MAX(id) AS latest_id
                            FROM encuestas
                            WHERE usuario_id IS NOT NULL
                            GROUP BY usuario_id
                        ) latest ON latest.latest_id = e1.id
                    ) e ON e.usuario_id = u.id
                """)
                rows = cursor.fetchall()
    else:
        try:
            rows = fetch_with_mysql_client(args)
        except (
            FileNotFoundError,
            subprocess.CalledProcessError
        ) as exc:
            print(
                "No se pudo leer la BD.",
                file=sys.stderr,
            )
            raise SystemExit(1) from exc

    # Calcula riesgo por estudiante
    enriched = []
    for row in rows:
        score = risk_score(row)
        enriched.append({
            "id": row["usuario_id"],
            "nombre": row["nombre"],
            "carrera": row["carrera"],
            "promedio": float(row["promedio"] or 0),
            "asistencia": int(row["asistencia"] or 0),
            "riesgo": risk_level(score),
            "puntuacion_riesgo": score,
        })

    # Lista con todas las puntuaciones
    scores = [
        item["puntuacion_riesgo"]
        for item in enriched
    ]

    # Contador de niveles de riesgo
    distribution = {
        "Bajo": 0,
        "Medio": 0,
        "Alto": 0,
    }

    for item in enriched:
        distribution[item["riesgo"]] += 1

    # Genera el reporte final
    output = {
        "total_estudiantes": len(enriched),
        "modelo": {
            "tipo": "suma ponderada",
            "bloques": {
                "academico": [
                    "promedio",
                    "materias_reprobadas",
                    "asistencia",
                ],
                "habitos": [
                    "horas_estudio",
                    "horas_sueno",
                    "uso_redes",
                ],
                "contexto": [
                    "nivel_estres",
                    "desmotivacion",
                    "administracion_tiempo",
                    "entrega_tareas",
                    "acceso_internet",
                    "espacio_estudio",
                    "trabaja",
                ],
            },
            "lectura": "mas puntos = mas riesgo",
        },
        "riesgo_promedio":
            round(mean(scores), 2)
            if scores else 0,
        "distribucion_riesgo":
            distribution,
        "mayor_riesgo":
            sorted(
                enriched,
                key=lambda item:
                item["puntuacion_riesgo"],
                reverse=True
            )[:args.limit],

        "mejor_promedio":
            sorted(
                enriched,
                key=lambda item:
                item["promedio"],
                reverse=True
            )[:args.limit],
    }

    # Mostrar el resultado en JSON
    print(
        json.dumps(
            output,
            ensure_ascii=False,
            indent=2
        )
    )

# Punto de entrada del programa
if __name__ == "__main__":
    main()
