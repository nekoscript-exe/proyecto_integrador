#!/usr/bin/env python3
"""
Procesa datasets CSV de rendimiento estudiantil para ATENEA.

Ciclo de vida aplicado:
1. Ingesta: lectura del CSV original.
2. Raw: guardado de datos crudos sin transformar.
3. Limpieza: conversion de tipos, booleanos y campos derivados.
4. Analisis: metricas generales y correlaciones simples.
5. Carga: almacenamiento en MariaDB para tablas y graficas.
"""

from __future__ import annotations

import argparse
import csv
import json
import math
import os
import sys
from dataclasses import dataclass
from datetime import datetime
from statistics import mean
from typing import Any

try:
    import pymysql
    import pymysql.cursors as pymysql_cursors
except ImportError:
    pymysql = None
    pymysql_cursors = None


REQUIRED_COLUMNS = [
    "Student_ID",
    "Age",
    "Gender",
    "Class",
    "Study_Hours_Per_Day",
    "Attendance_Percentage",
    "Parental_Education",
    "Internet_Access",
    "Extracurricular_Activities",
    "Math_Score",
    "Science_Score",
    "English_Score",
    "Previous_Year_Score",
    "Final_Percentage",
    "Performance_Level",
    "Pass_Fail",
]


@dataclass
class ProcessedDataset:
    raw_rows: list[dict[str, Any]]
    clean_rows: list[dict[str, Any]]
    analysis: dict[str, Any]
    skipped_rows: int


def normalize_key(key: str) -> str:
    return key.strip().lower().replace(" ", "_")


def get_value(row: dict[str, str], column: str) -> str:
    aliases = {normalize_key(key): key for key in row.keys()}
    original_key = aliases.get(normalize_key(column))
    if original_key is None:
        return ""
    return (row.get(original_key) or "").strip()


def parse_int(value: str) -> int | None:
    value = value.strip()
    if value == "":
        return None
    return int(float(value.replace(",", ".")))


def parse_float(value: str) -> float | None:
    value = value.strip()
    if value == "":
        return None
    return float(value.replace(",", "."))


def parse_yes_no(value: str) -> int | None:
    value = value.strip().lower()
    if value in {"yes", "si", "true", "1"}:
        return 1
    if value in {"no", "false", "0"}:
        return 0
    return None


def parse_pass_fail(value: str) -> int | None:
    value = value.strip().lower()
    if value in {"pass", "aprobado", "aprobada", "1", "true"}:
        return 1
    if value in {"fail", "reprobado", "reprobada", "0", "false"}:
        return 0
    return None


def normalize_gender(value: str) -> str:
    value = value.strip().lower()
    if value == "male":
        return "Masculino"
    if value == "female":
        return "Femenino"
    return value.title()


def raw_yes_no(value: str) -> str | None:
    parsed = parse_yes_no(value)
    if parsed is None:
        return None
    return "Yes" if parsed == 1 else "No"


def round_or_none(value: float | None, decimals: int = 2) -> float | None:
    if value is None:
        return None
    return round(value, decimals)


def pearson_correlation(xs: list[float], ys: list[float]) -> float:
    if len(xs) < 2 or len(xs) != len(ys):
        return 0.0

    mean_x = mean(xs)
    mean_y = mean(ys)
    numerator = sum((x - mean_x) * (y - mean_y) for x, y in zip(xs, ys))
    denominator_x = math.sqrt(sum((x - mean_x) ** 2 for x in xs))
    denominator_y = math.sqrt(sum((y - mean_y) ** 2 for y in ys))

    if denominator_x == 0 or denominator_y == 0:
        return 0.0

    return round(numerator / (denominator_x * denominator_y), 4)


def read_csv(path: str) -> list[dict[str, str]]:
    with open(path, newline="", encoding="utf-8-sig") as file:
        reader = csv.DictReader(file)
        if not reader.fieldnames:
            raise ValueError("El CSV no tiene encabezados.")

        fieldnames = {normalize_key(name) for name in reader.fieldnames}
        missing = [
            column
            for column in REQUIRED_COLUMNS
            if normalize_key(column) not in fieldnames
        ]
        if missing:
            raise ValueError("Faltan columnas requeridas: " + ", ".join(missing))

        return list(reader)


def build_rows(csv_rows: list[dict[str, str]]) -> ProcessedDataset:
    raw_rows: list[dict[str, Any]] = []
    clean_rows: list[dict[str, Any]] = []
    skipped_rows = 0

    for row in csv_rows:
        raw = {
            "student_id": get_value(row, "Student_ID"),
            "age": parse_int(get_value(row, "Age")),
            "gender": get_value(row, "Gender"),
            "class": parse_int(get_value(row, "Class")),
            "study_hours_per_day": parse_float(get_value(row, "Study_Hours_Per_Day")),
            "attendance_percentage": parse_float(get_value(row, "Attendance_Percentage")),
            "parental_education": get_value(row, "Parental_Education"),
            "internet_access": raw_yes_no(get_value(row, "Internet_Access")),
            "extracurricular_activities": raw_yes_no(get_value(row, "Extracurricular_Activities")),
            "math_score": parse_int(get_value(row, "Math_Score")),
            "science_score": parse_int(get_value(row, "Science_Score")),
            "english_score": parse_int(get_value(row, "English_Score")),
            "previous_year_score": parse_float(get_value(row, "Previous_Year_Score")),
            "final_percentage": parse_float(get_value(row, "Final_Percentage")),
            "performance_level": get_value(row, "Performance_Level"),
            "pass_fail": get_value(row, "Pass_Fail"),
        }
        raw_rows.append(raw)

        critical = [
            raw["student_id"],
            raw["age"],
            raw["study_hours_per_day"],
            raw["attendance_percentage"],
            raw["math_score"],
            raw["science_score"],
            raw["english_score"],
            raw["final_percentage"],
        ]
        if any(value is None or value == "" for value in critical):
            skipped_rows += 1
            continue

        subject_scores = [
            float(raw["math_score"]),
            float(raw["science_score"]),
            float(raw["english_score"]),
        ]

        clean_rows.append({
            "student_id": raw["student_id"],
            "age": raw["age"],
            "gender": normalize_gender(raw["gender"]),
            "class": raw["class"],
            "study_hours_per_day": round_or_none(raw["study_hours_per_day"]),
            "attendance_percentage": round_or_none(raw["attendance_percentage"]),
            "parental_education": raw["parental_education"].strip().title(),
            "internet_access": parse_yes_no(get_value(row, "Internet_Access")),
            "extracurricular_activities": parse_yes_no(get_value(row, "Extracurricular_Activities")),
            "math_score": raw["math_score"],
            "science_score": raw["science_score"],
            "english_score": raw["english_score"],
            "previous_year_score": round_or_none(raw["previous_year_score"]),
            "final_percentage": round_or_none(raw["final_percentage"]),
            "performance_level": raw["performance_level"].strip().title(),
            "pass_fail": parse_pass_fail(raw["pass_fail"]),
            "promedio_materias": round(mean(subject_scores), 2),
        })

    analysis = analyze(clean_rows)
    return ProcessedDataset(raw_rows, clean_rows, analysis, skipped_rows)


def analyze(clean_rows: list[dict[str, Any]]) -> dict[str, Any]:
    if not clean_rows:
        return {
            "total_students": 0,
            "promedio_general": 0,
            "porcentaje_aprobados": 0,
            "porcentaje_reprobados": 0,
            "promedio_matematicas": 0,
            "promedio_ciencias": 0,
            "promedio_ingles": 0,
            "correlacion_estudio_desempeno": 0,
            "correlacion_asistencia_desempeno": 0,
        }

    total = len(clean_rows)
    passed = sum(1 for row in clean_rows if row["pass_fail"] == 1)
    final_scores = [float(row["final_percentage"]) for row in clean_rows]
    study_hours = [float(row["study_hours_per_day"]) for row in clean_rows]
    attendance = [float(row["attendance_percentage"]) for row in clean_rows]

    return {
        "total_students": total,
        "promedio_general": round(mean(final_scores), 2),
        "porcentaje_aprobados": round((passed / total) * 100, 2),
        "porcentaje_reprobados": round(((total - passed) / total) * 100, 2),
        "promedio_matematicas": round(mean(float(row["math_score"]) for row in clean_rows), 2),
        "promedio_ciencias": round(mean(float(row["science_score"]) for row in clean_rows), 2),
        "promedio_ingles": round(mean(float(row["english_score"]) for row in clean_rows), 2),
        "correlacion_estudio_desempeno": pearson_correlation(study_hours, final_scores),
        "correlacion_asistencia_desempeno": pearson_correlation(attendance, final_scores),
    }


def connect(args: argparse.Namespace):
    if pymysql is None or pymysql_cursors is None:
        raise RuntimeError("pymysql no esta instalado. Ejecuta pip install -r requirements.txt")

    return pymysql.connect(
        host=args.host,
        user=args.user,
        password=args.password,
        database=args.database,
        charset="utf8mb4",
        cursorclass=pymysql_cursors.DictCursor,
        autocommit=False,
    )


def table_exists(cursor, table: str) -> bool:
    cursor.execute("SHOW TABLES LIKE %s", (table,))
    return cursor.fetchone() is not None


def column_exists(cursor, table: str, column: str) -> bool:
    cursor.execute(f"SHOW COLUMNS FROM `{table}` LIKE %s", (column,))
    return cursor.fetchone() is not None


def ensure_schema(cursor) -> None:
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS dataset_uploads (
            id int(11) NOT NULL AUTO_INCREMENT,
            nombre_original varchar(255) NOT NULL,
            archivo_guardado varchar(255) DEFAULT NULL,
            fuente varchar(100) DEFAULT 'Kaggle',
            filas_raw int(11) DEFAULT 0,
            filas_clean int(11) DEFAULT 0,
            estado enum('procesando','completado','error') NOT NULL DEFAULT 'procesando',
            mensaje text DEFAULT NULL,
            uploaded_by int(11) DEFAULT NULL,
            fecha_carga timestamp NOT NULL DEFAULT current_timestamp(),
            fecha_procesado datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY uploaded_by (uploaded_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    """)

    if not table_exists(cursor, "dataset_estudiantes_raw"):
        cursor.execute("""
            CREATE TABLE dataset_estudiantes_raw (
                id int(11) NOT NULL AUTO_INCREMENT,
                dataset_upload_id int(11) DEFAULT NULL,
                student_id varchar(20) DEFAULT NULL,
                age int(11) DEFAULT NULL,
                gender varchar(20) DEFAULT NULL,
                `class` int(11) DEFAULT NULL,
                study_hours_per_day decimal(4,2) DEFAULT NULL,
                attendance_percentage decimal(5,2) DEFAULT NULL,
                parental_education varchar(50) DEFAULT NULL,
                internet_access enum('Yes','No') DEFAULT NULL,
                extracurricular_activities enum('Yes','No') DEFAULT NULL,
                math_score int(11) DEFAULT NULL,
                science_score int(11) DEFAULT NULL,
                english_score int(11) DEFAULT NULL,
                previous_year_score decimal(5,2) DEFAULT NULL,
                final_percentage decimal(5,2) DEFAULT NULL,
                performance_level varchar(20) DEFAULT NULL,
                pass_fail varchar(10) DEFAULT NULL,
                fecha_carga timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (id),
                KEY dataset_upload_id (dataset_upload_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        """)

    if not table_exists(cursor, "dataset_estudiantes_clean"):
        cursor.execute("""
            CREATE TABLE dataset_estudiantes_clean (
                id int(11) NOT NULL AUTO_INCREMENT,
                dataset_upload_id int(11) DEFAULT NULL,
                student_id varchar(20) DEFAULT NULL,
                age int(11) DEFAULT NULL,
                gender varchar(20) DEFAULT NULL,
                `class` int(11) DEFAULT NULL,
                study_hours_per_day decimal(4,2) DEFAULT NULL,
                attendance_percentage decimal(5,2) DEFAULT NULL,
                parental_education varchar(50) DEFAULT NULL,
                internet_access tinyint(1) DEFAULT NULL,
                extracurricular_activities tinyint(1) DEFAULT NULL,
                math_score int(11) DEFAULT NULL,
                science_score int(11) DEFAULT NULL,
                english_score int(11) DEFAULT NULL,
                previous_year_score decimal(5,2) DEFAULT NULL,
                final_percentage decimal(5,2) DEFAULT NULL,
                performance_level varchar(20) DEFAULT NULL,
                pass_fail tinyint(1) DEFAULT NULL,
                promedio_materias decimal(5,2) DEFAULT NULL,
                fecha_procesado timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (id),
                KEY dataset_upload_id (dataset_upload_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        """)

    if not table_exists(cursor, "dataset_analysis_results"):
        cursor.execute("""
            CREATE TABLE dataset_analysis_results (
                id int(11) NOT NULL AUTO_INCREMENT,
                dataset_upload_id int(11) DEFAULT NULL,
                total_students int(11) DEFAULT NULL,
                promedio_general decimal(5,2) DEFAULT NULL,
                porcentaje_aprobados decimal(5,2) DEFAULT NULL,
                porcentaje_reprobados decimal(5,2) DEFAULT NULL,
                promedio_matematicas decimal(5,2) DEFAULT NULL,
                promedio_ciencias decimal(5,2) DEFAULT NULL,
                promedio_ingles decimal(5,2) DEFAULT NULL,
                correlacion_estudio_desempeno decimal(6,4) DEFAULT NULL,
                correlacion_asistencia_desempeno decimal(6,4) DEFAULT NULL,
                fecha_analisis timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (id),
                KEY dataset_upload_id (dataset_upload_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        """)

    for table in [
        "dataset_estudiantes_raw",
        "dataset_estudiantes_clean",
        "dataset_analysis_results",
    ]:
        if not column_exists(cursor, table, "dataset_upload_id"):
            cursor.execute(f"ALTER TABLE `{table}` ADD COLUMN dataset_upload_id int(11) DEFAULT NULL AFTER id")
            cursor.execute(f"ALTER TABLE `{table}` ADD KEY dataset_upload_id (dataset_upload_id)")

    if not column_exists(cursor, "dataset_estudiantes_clean", "class"):
        cursor.execute("ALTER TABLE `dataset_estudiantes_clean` ADD COLUMN `class` int(11) DEFAULT NULL AFTER gender")


def create_upload(cursor, args: argparse.Namespace) -> int:
    if args.upload_id:
        cursor.execute(
            """
            UPDATE dataset_uploads
            SET estado = 'procesando',
                mensaje = NULL,
                archivo_guardado = %s
            WHERE id = %s
            """,
            (args.stored_path, args.upload_id),
        )
        return args.upload_id

    cursor.execute(
        """
        INSERT INTO dataset_uploads
        (
            nombre_original,
            archivo_guardado,
            fuente,
            uploaded_by,
            estado
        )
        VALUES (%s, %s, %s, %s, 'procesando')
        """,
        (
            args.source_name or os.path.basename(args.csv_path),
            args.stored_path or args.csv_path,
            args.source or "Kaggle",
            args.uploaded_by,
        ),
    )
    return int(cursor.lastrowid)


def insert_dataset(cursor, upload_id: int, processed: ProcessedDataset) -> None:
    cursor.execute("DELETE FROM dataset_analysis_results WHERE dataset_upload_id = %s", (upload_id,))
    cursor.execute("DELETE FROM dataset_estudiantes_clean WHERE dataset_upload_id = %s", (upload_id,))
    cursor.execute("DELETE FROM dataset_estudiantes_raw WHERE dataset_upload_id = %s", (upload_id,))

    raw_values = [
        (
            upload_id,
            row["student_id"],
            row["age"],
            row["gender"],
            row["class"],
            row["study_hours_per_day"],
            row["attendance_percentage"],
            row["parental_education"],
            row["internet_access"],
            row["extracurricular_activities"],
            row["math_score"],
            row["science_score"],
            row["english_score"],
            row["previous_year_score"],
            row["final_percentage"],
            row["performance_level"],
            row["pass_fail"],
        )
        for row in processed.raw_rows
    ]
    if raw_values:
        cursor.executemany(
            """
            INSERT INTO dataset_estudiantes_raw
            (
                dataset_upload_id,
                student_id,
                age,
                gender,
                `class`,
                study_hours_per_day,
                attendance_percentage,
                parental_education,
                internet_access,
                extracurricular_activities,
                math_score,
                science_score,
                english_score,
                previous_year_score,
                final_percentage,
                performance_level,
                pass_fail
            )
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
            """,
            raw_values,
        )

    clean_values = [
        (
            upload_id,
            row["student_id"],
            row["age"],
            row["gender"],
            row["class"],
            row["study_hours_per_day"],
            row["attendance_percentage"],
            row["parental_education"],
            row["internet_access"],
            row["extracurricular_activities"],
            row["math_score"],
            row["science_score"],
            row["english_score"],
            row["previous_year_score"],
            row["final_percentage"],
            row["performance_level"],
            row["pass_fail"],
            row["promedio_materias"],
        )
        for row in processed.clean_rows
    ]
    if clean_values:
        cursor.executemany(
            """
            INSERT INTO dataset_estudiantes_clean
            (
                dataset_upload_id,
                student_id,
                age,
                gender,
                `class`,
                study_hours_per_day,
                attendance_percentage,
                parental_education,
                internet_access,
                extracurricular_activities,
                math_score,
                science_score,
                english_score,
                previous_year_score,
                final_percentage,
                performance_level,
                pass_fail,
                promedio_materias
            )
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
            """,
            clean_values,
        )

    analysis = processed.analysis
    cursor.execute(
        """
        INSERT INTO dataset_analysis_results
        (
            dataset_upload_id,
            total_students,
            promedio_general,
            porcentaje_aprobados,
            porcentaje_reprobados,
            promedio_matematicas,
            promedio_ciencias,
            promedio_ingles,
            correlacion_estudio_desempeno,
            correlacion_asistencia_desempeno
        )
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
        """,
        (
            upload_id,
            analysis["total_students"],
            analysis["promedio_general"],
            analysis["porcentaje_aprobados"],
            analysis["porcentaje_reprobados"],
            analysis["promedio_matematicas"],
            analysis["promedio_ciencias"],
            analysis["promedio_ingles"],
            analysis["correlacion_estudio_desempeno"],
            analysis["correlacion_asistencia_desempeno"],
        ),
    )

    cursor.execute(
        """
        UPDATE dataset_uploads
        SET filas_raw = %s,
            filas_clean = %s,
            estado = 'completado',
            mensaje = %s,
            fecha_procesado = %s
        WHERE id = %s
        """,
        (
            len(processed.raw_rows),
            len(processed.clean_rows),
            f"Filas omitidas en limpieza: {processed.skipped_rows}",
            datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
            upload_id,
        ),
    )


def mark_upload_error(cursor, upload_id: int | None, message: str) -> None:
    if upload_id is None:
        return
    cursor.execute(
        """
        UPDATE dataset_uploads
        SET estado = 'error',
            mensaje = %s,
            fecha_procesado = %s
        WHERE id = %s
        """,
        (message[:1000], datetime.now().strftime("%Y-%m-%d %H:%M:%S"), upload_id),
    )


def output_json(payload: dict[str, Any]) -> None:
    print(json.dumps(payload, ensure_ascii=False, indent=2))


def main() -> None:
    parser = argparse.ArgumentParser(
        description="Procesa un CSV de rendimiento estudiantil para ATENEA."
    )
    parser.add_argument("csv_path")
    parser.add_argument("--host", default="localhost")
    parser.add_argument("--user", default="root")
    parser.add_argument("--password", default="")
    parser.add_argument("--database", default="atenea")
    parser.add_argument("--uploaded-by", type=int, default=None)
    parser.add_argument("--source-name", default=None)
    parser.add_argument("--stored-path", default=None)
    parser.add_argument("--source", default="Kaggle")
    parser.add_argument("--upload-id", type=int, default=None)
    parser.add_argument("--dry-run", action="store_true")
    args = parser.parse_args()

    csv_rows = read_csv(args.csv_path)
    processed = build_rows(csv_rows)

    if args.dry_run:
        output_json({
            "ok": True,
            "mode": "dry-run",
            "raw_rows": len(processed.raw_rows),
            "clean_rows": len(processed.clean_rows),
            "skipped_rows": processed.skipped_rows,
            "analysis": processed.analysis,
        })
        return

    upload_id: int | None = None
    conn = connect(args)
    try:
        with conn.cursor() as cursor:
            ensure_schema(cursor)
            upload_id = create_upload(cursor, args)
            insert_dataset(cursor, upload_id, processed)
        conn.commit()
    except Exception as exc:
        conn.rollback()
        try:
            with conn.cursor() as cursor:
                mark_upload_error(cursor, upload_id, str(exc))
            conn.commit()
        except Exception:
            conn.rollback()
        raise
    finally:
        conn.close()

    output_json({
        "ok": True,
        "upload_id": upload_id,
        "raw_rows": len(processed.raw_rows),
        "clean_rows": len(processed.clean_rows),
        "skipped_rows": processed.skipped_rows,
        "analysis": processed.analysis,
    })


if __name__ == "__main__":
    try:
        main()
    except Exception as error:
        output_json({
            "ok": False,
            "error": str(error),
        })
        sys.exit(1)
