#!/usr/bin/env python3
"""
Analizador local para Atenea.

Lee la base de datos MariaDB/MySQL y genera un resumen JSON con metricas
generales, distribucion de riesgo y perfiles destacados. Requiere:

    pip install pymysql

Ejemplo:

    python3 PYTHON/risk_analysis.py --host localhost --user root --database atenea
"""

from __future__ import annotations

import argparse
import csv
import json
import subprocess
import sys
from statistics import mean

try:
    import pymysql
except ImportError:
    pymysql = None


def risk_score(row: dict) -> float:
    score = 0.0

    promedio = float(row.get("promedio") or 0)
    reprobadas = int(row.get("materias_reprobadas") or 0)
    asistencia = int(row.get("asistencia") or 0)
    estudio = float(row.get("horas_estudio") or 0)
    sueno = float(row.get("horas_sueno") or 0)
    redes = float(row.get("uso_redes") or 0)
    estres = int(row.get("nivel_estres") or 0)
    desmotivacion = int(row.get("desmotivacion") or 0)
    tiempo = int(row.get("administracion_tiempo") or 0)
    entrega = int(row.get("entrega_tareas") or 3)

    score += 24 if promedio < 6 else 14 if promedio < 7.5 else 6 if promedio < 8.5 else 0
    score += min(18, reprobadas * 6)
    score += 18 if asistencia < 70 else 10 if asistencia < 85 else 4 if asistencia < 93 else 0
    score += 10 if estudio < 1 else 5 if estudio < 2 else 0
    score += 10 if sueno < 5 else 5 if sueno < 6.5 else 0
    score += 8 if redes > 6 else 4 if redes > 4 else 0
    score += 12 if estres >= 8 else 6 if estres >= 5 else 0
    score += 10 if desmotivacion >= 4 else 5 if desmotivacion >= 3 else 0
    score += 8 if tiempo <= 2 else 3 if tiempo == 3 else 0
    score += 8 if entrega <= 2 else 0
    score += 6 if int(row.get("acceso_internet") or 1) == 0 else 0
    score += 6 if int(row.get("espacio_estudio") or 1) == 0 else 0
    score += 4 if int(row.get("trabaja") or 0) == 1 else 0

    return round(min(100, score), 2)


def risk_level(score: float) -> str:
    if score >= 60:
        return "Alto"
    if score >= 32:
        return "Medio"
    return "Bajo"


def connect(args: argparse.Namespace):
    if pymysql is None:
        raise RuntimeError("pymysql no esta instalado")

    return pymysql.connect(
        host=args.host,
        user=args.user,
        password=args.password,
        database=args.database,
        charset="utf8mb4",
        cursorclass=pymysql.cursors.DictCursor,
    )


def fetch_with_mysql_client(args: argparse.Namespace) -> list[dict]:
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
    command = [
        args.mysql_bin,
        "--batch",
        "--raw",
        f"--host={args.host}",
        f"--user={args.user}",
        f"--database={args.database}",
        f"--execute={query}",
    ]

    if args.password:
        command.insert(4, f"--password={args.password}")

    completed = subprocess.run(
        command,
        check=True,
        capture_output=True,
        text=True,
    )

    return list(csv.DictReader(completed.stdout.splitlines(), delimiter="\t"))


def main() -> None:
    parser = argparse.ArgumentParser(description="Analiza datos academicos de Atenea.")
    parser.add_argument("--host", default="localhost")
    parser.add_argument("--user", default="root")
    parser.add_argument("--password", default="")
    parser.add_argument("--database", default="atenea")
    parser.add_argument("--limit", type=int, default=10)
    parser.add_argument("--mysql-bin", default="/opt/lampp/bin/mysql")
    args = parser.parse_args()

    if pymysql is not None:
        with connect(args) as conn:
            with conn.cursor() as cursor:
                cursor.execute(
                    """
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
                    """
                )
                rows = cursor.fetchall()
    else:
        try:
            rows = fetch_with_mysql_client(args)
        except (FileNotFoundError, subprocess.CalledProcessError) as exc:
            print(
                "No se pudo leer la BD. Instala pymysql o revisa --mysql-bin.",
                file=sys.stderr,
            )
            raise SystemExit(1) from exc

    enriched = []
    for row in rows:
        score = risk_score(row)
        enriched.append(
            {
                "id": row["usuario_id"],
                "nombre": row["nombre"],
                "carrera": row["carrera"],
                "promedio": float(row["promedio"] or 0),
                "asistencia": int(row["asistencia"] or 0),
                "riesgo": risk_level(score),
                "puntuacion_riesgo": score,
            }
        )

    scores = [item["puntuacion_riesgo"] for item in enriched]
    distribution = {"Bajo": 0, "Medio": 0, "Alto": 0}
    for item in enriched:
        distribution[item["riesgo"]] += 1

    output = {
        "total_estudiantes": len(enriched),
        "riesgo_promedio": round(mean(scores), 2) if scores else 0,
        "distribucion_riesgo": distribution,
        "mayor_riesgo": sorted(enriched, key=lambda item: item["puntuacion_riesgo"], reverse=True)[: args.limit],
        "mejor_promedio": sorted(enriched, key=lambda item: item["promedio"], reverse=True)[: args.limit],
    }

    print(json.dumps(output, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
