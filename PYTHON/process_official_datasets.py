"""Procesa los XLSX oficiales de eficiencia educativa para ATENEA."""

# Importamos json para guardar resultados que PHP pueda leer facil.
import json
# Importamos math para revisar valores numericos sin hacer magia rara.
import math
# Importamos re para limpiar textos y nombres de columnas.
import re
# Importamos unicodedata para quitar acentos al comparar encabezados.
import unicodedata
# Importamos Path para trabajar rutas de forma ordenada.
from pathlib import Path

# Importamos matplotlib para crear las graficas en imagen.
import matplotlib.pyplot as plt
# Importamos numpy para calculos numericos pequenos.
import numpy as np
# Importamos pandas para leer, limpiar y resumir los archivos Excel.
import pandas as pd


# Definimos la carpeta raiz del proyecto.
PROJECT_ROOT = Path(__file__).resolve().parents[1]
# Definimos la carpeta donde viven los XLSX reales.
DATASET_DIR = PROJECT_ROOT / "DATASETS"
# Definimos la carpeta donde quedaran los archivos procesados.
OUTPUT_DIR = DATASET_DIR / "processed"
# Definimos la carpeta donde quedaran las graficas generadas.
CHART_DIR = OUTPUT_DIR / "charts"
# Definimos el JSON principal que consumira LandingPage.php.
SUMMARY_JSON = OUTPUT_DIR / "landing_metrics.json"
# Definimos el CSV limpio para auditoria y trabajo futuro.
CLEAN_CSV = OUTPUT_DIR / "official_education_clean.csv"
# Definimos el notebook que documenta el analisis.
NOTEBOOK_FILE = PROJECT_ROOT / "Atenea_Datasets_Oficiales.ipynb"


# Coordenadas aproximadas de municipios de Queretaro para el mapa visual.
MUNICIPALITY_COORDS = {
    "AMEALCO DE BONFIL": (20.19, -100.14),
    "ARROYO SECO": (21.55, -99.69),
    "CADEREYTA DE MONTES": (20.69, -99.82),
    "COLON": (20.78, -100.05),
    "CORREGIDORA": (20.54, -100.44),
    "EL MARQUES": (20.61, -100.24),
    "EZEQUIEL MONTES": (20.66, -99.90),
    "HUIMILPAN": (20.37, -100.28),
    "JALPAN DE SERRA": (21.22, -99.47),
    "LANDA DE MATAMOROS": (21.18, -99.32),
    "PEDRO ESCOBEDO": (20.50, -100.14),
    "PENAMILLER": (21.05, -99.81),
    "PINAL DE AMOLES": (21.13, -99.63),
    "QUERETARO": (20.59, -100.39),
    "SAN JOAQUIN": (20.91, -99.57),
    "SAN JUAN DEL RIO": (20.39, -99.99),
    "TEQUISQUIAPAN": (20.52, -99.89),
    "TOLIMAN": (20.91, -99.93),
}


def strip_accents(value: str) -> str:
    # Convertimos el texto a forma descompuesta para separar letras y acentos.
    normalized = unicodedata.normalize("NFKD", str(value))
    # Quitamos marcas de acento y dejamos solo caracteres base.
    return "".join(char for char in normalized if not unicodedata.combining(char))


def normalize_text(value) -> str:
    # Convertimos cualquier valor a texto para evitar errores con numeros o NaN.
    text = str(value if value is not None else "")
    # Quitamos acentos para poder comparar encabezados de forma estable.
    text = strip_accents(text)
    # Reemplazamos saltos de linea por espacios para unificar encabezados.
    text = text.replace("\n", " ")
    # Convertimos a mayusculas porque los reportes no siempre usan el mismo formato.
    text = text.upper()
    # Compactamos espacios multiples para que las busquedas sean confiables.
    text = " ".join(text.split())
    # Regresamos el texto listo para comparar.
    return text


def unique_columns(columns: list[str]) -> list[str]:
    # Creamos un diccionario para contar columnas repetidas.
    seen: dict[str, int] = {}
    # Creamos la lista final de columnas ya uniquificadas.
    result: list[str] = []
    # Recorremos cada nombre calculado desde el Excel.
    for column in columns:
        # Guardamos cuantas veces ha aparecido ese nombre.
        count = seen.get(column, 0)
        # Usamos el nombre original si es la primera aparicion.
        result.append(column if count == 0 else f"{column} #{count + 1}")
        # Actualizamos el contador para ese nombre.
        seen[column] = count + 1
    # Regresamos la lista segura para pandas.
    return result


def base_column(column: str) -> str:
    # Quitamos el sufijo que agregamos a columnas duplicadas.
    return column.split(" #", 1)[0]


def leaf_label(column: str) -> str:
    # Tomamos el ultimo segmento del encabezado jerarquico.
    return base_column(column).split("|")[-1].strip()


def find_header_row(frame: pd.DataFrame) -> int:
    # Recorremos todas las filas para encontrar la fila con encabezados reales.
    for index in range(len(frame)):
        # Normalizamos todos los valores no vacios de la fila actual.
        values = [normalize_text(value) for value in frame.iloc[index].dropna().tolist()]
        # La fila real suele contener TIPO, MUNICIPIO y muchos encabezados.
        if "TIPO" in values and "MUNICIPIO" in values and len(values) > 20:
            # Regresamos el indice cuando encontramos la fila correcta.
            return index
    # Si no encontramos encabezado, detenemos el proceso con un mensaje claro.
    raise ValueError("No se encontro la fila de encabezados en el archivo.")


def build_columns(frame: pd.DataFrame, header_row: int) -> list[str]:
    # Guardamos filas de encabezado de varios niveles.
    header_levels: list[list[str]] = []
    # Usamos hasta dos filas superiores y la fila final con nombres reales.
    for row_index in [header_row - 2, header_row - 1, header_row]:
        # Ignoramos indices negativos por seguridad.
        if row_index < 0:
            continue
        # Tomamos la fila del encabezado actual.
        row = frame.iloc[row_index]
        # Rellenamos horizontalmente solo las filas agrupadoras.
        if row_index != header_row:
            row = row.ffill()
        # Normalizamos cada celda para construir nombres comparables.
        header_levels.append([normalize_text(value) for value in row.fillna("").tolist()])
    # Preparamos la lista de nombres finales.
    columns: list[str] = []
    # Recorremos cada columna numerica del Excel.
    for column_index in range(frame.shape[1]):
        # Guardamos los segmentos utiles de esa columna.
        parts: list[str] = []
        # Revisamos cada nivel de encabezado.
        for level in header_levels:
            # Tomamos el texto del nivel actual.
            part = level[column_index]
            # Evitamos NaN textual, blancos y duplicados.
            if part and part != "NAN" and part not in parts:
                parts.append(part)
        # Unimos los segmentos para formar un encabezado jerarquico.
        columns.append(" | ".join(parts) if parts else f"C{column_index}")
    # Regresamos nombres unicos para evitar problemas con pandas.
    return unique_columns(columns)


def pick_by_leaf(columns: list[str], target: str) -> str | None:
    # Buscamos una columna cuyo ultimo segmento sea exactamente el objetivo.
    for column in columns:
        # Comparamos contra la hoja final del encabezado.
        if leaf_label(column) == target:
            return column
    # Regresamos None si no existe esa columna.
    return None


def pick_column(columns: list[str], includes: list[str], excludes: list[str] | None = None) -> str | None:
    # Normalizamos la lista de exclusiones.
    excludes = excludes or []
    # Recorremos columnas en orden para tomar la primera coincidencia valida.
    for column in columns:
        # Quitamos sufijos de duplicado antes de comparar.
        candidate = base_column(column)
        # Validamos que todas las palabras requeridas esten presentes.
        has_required = all(word in candidate for word in includes)
        # Validamos que ninguna palabra prohibida este presente.
        has_forbidden = any(word in candidate for word in excludes)
        # Si cumple, devolvemos esa columna.
        if has_required and not has_forbidden:
            return column
    # Si no hubo match, regresamos None.
    return None


def choose_column(columns: list[str], patterns: list[tuple[list[str], list[str]]]) -> str | None:
    # Probamos varios patrones porque los reportes cambian entre ciclos.
    for includes, excludes in patterns:
        # Intentamos encontrar una columna con el patron actual.
        column = pick_column(columns, includes, excludes)
        # Si existe, la regresamos.
        if column:
            return column
    # Si ningun patron funciono, regresamos None.
    return None


def clean_string_series(series: pd.Series) -> pd.Series:
    # Convertimos a texto para limpiar valores mixtos.
    cleaned = series.astype(str).str.strip()
    # Convertimos textos vacios o nan textual a NaN real.
    cleaned = cleaned.replace({"": np.nan, "nan": np.nan, "NAN": np.nan, "None": np.nan})
    # Regresamos la serie limpia.
    return cleaned


def to_number(series: pd.Series) -> pd.Series:
    # Convertimos cualquier valor a numero y dejamos NaN si no se puede.
    return pd.to_numeric(series, errors="coerce")


def detect_report_level(path: Path) -> str:
    # Revisamos el nombre del archivo para saber si es media superior.
    if "MEDIA" in normalize_text(path.name):
        return "Media superior"
    # Si no es media superior, este paquete corresponde a secundaria.
    return "Secundaria"


def parse_workbook(path: Path) -> pd.DataFrame:
    # Leemos el XLSX sin encabezados para poder controlar la limpieza.
    raw = pd.read_excel(path, header=None)
    # Encontramos la fila donde inicia la tabla real.
    header_row = find_header_row(raw)
    # Construimos encabezados jerarquicos limpios.
    columns = build_columns(raw, header_row)
    # Tomamos solo las filas posteriores al encabezado.
    data = raw.iloc[header_row + 1 :].copy()
    # Asignamos los nombres de columnas ya limpiados.
    data.columns = columns
    # Definimos las columnas clave que necesitamos para el analisis.
    selected = {
        "nivel": pick_by_leaf(columns, "NIVEL"),
        "subnivel": pick_by_leaf(columns, "SUBNIVEL"),
        "control": pick_by_leaf(columns, "CONTROL"),
        "sostenimiento": pick_by_leaf(columns, "TIPO SOSTENIMIENTO"),
        "poblacion": pick_by_leaf(columns, "CATEGORIA POBLACION"),
        "marginacion": pick_by_leaf(columns, "GRADO MARGINACION"),
        "municipio": pick_by_leaf(columns, "MUNICIPIO"),
        "localidad": pick_by_leaf(columns, "LOCALIDAD"),
        "escuela": choose_column(columns, [(["ESCUELA", "NOMBRE"], []), (["DATOS IDENTIFICACION", "NOMBRE"], [])]),
        "existencia": choose_column(columns, [(["EXISTENCIA", "TOTAL"], ["HOMBRES", "MUJERES"])]),
        "aprobados": choose_column(columns, [(["APROBADOS", "TOTAL"], ["HOMBRES", "MUJERES"]), (["PROMOVIDOS", "TOTAL"], ["HOMBRES", "MUJERES"])]),
        "regularizados": choose_column(columns, [(["REGULARIZADOS", "TOTAL"], ["HOMBRES", "MUJERES"]), (["REGULARIZADOS"], ["REPROB", "HOMBRES", "MUJERES", "PRIMERO", "SEGUNDO", "TERCERO"])]),
        "reprobados": choose_column(columns, [(["REPROB", "ABSOLUTO"], ["HOMBRES", "MUJERES"]), (["REPROB", "TOTAL"], ["HOMBRES", "MUJERES"])]),
        "reprobacion_pct": choose_column(columns, [(["REPROB", "%"], ["HOMBRES", "MUJERES"])]),
        "desertores": choose_column(columns, [(["DESER", "DESERTORES TOTALES"], ["HOMBRES", "MUJERES"]), (["DESER", "ABSOLUTO"], ["HOMBRES", "MUJERES"])]),
        "desercion_pct": choose_column(columns, [(["DESER", "%"], ["HOMBRES", "MUJERES"])]),
        "egresion_pct": choose_column(columns, [(["EGRESION", "COEF"], ["HOMBRES", "MUJERES"])]),
        "eficiencia_terminal_pct": choose_column(columns, [(["EFICIENCIA TERMINAL", "%"], ["HOMBRES", "MUJERES"])]),
    }
    # Creamos el dataset limpio con el mismo indice de las filas originales.
    clean = pd.DataFrame(index=data.index)
    # Guardamos el ciclo escolar desde el nombre de la carpeta.
    clean["periodo"] = path.parent.name
    # Guardamos el nivel detectado desde el nombre del archivo.
    clean["nivel_reporte"] = detect_report_level(path)
    # Copiamos cada columna seleccionada al dataset limpio.
    for target, source in selected.items():
        clean[target] = data[source] if source else np.nan
    # Limpiamos columnas textuales importantes.
    for column in ["nivel", "subnivel", "control", "sostenimiento", "poblacion", "marginacion", "municipio", "localidad", "escuela"]:
        clean[column] = clean_string_series(clean[column])
    # Convertimos columnas numericas a float.
    for column in ["existencia", "aprobados", "regularizados", "reprobados", "reprobacion_pct", "desertores", "desercion_pct", "egresion_pct", "eficiencia_terminal_pct"]:
        clean[column] = to_number(clean[column])
    # Quitamos filas sin municipio o sin matricula existente.
    clean = clean[clean["municipio"].notna() & clean["existencia"].notna() & (clean["existencia"] > 0)].copy()
    # Normalizamos municipio para que acentos no separen categorias iguales.
    clean["municipio_normalizado"] = clean["municipio"].map(lambda value: normalize_text(value))
    # Calculamos reprobacion si el porcentaje no venia disponible.
    clean["reprobacion_pct_calc"] = clean["reprobacion_pct"].fillna((clean["reprobados"] / clean["existencia"]) * 100)
    # Calculamos desercion si el porcentaje no venia disponible.
    clean["desercion_pct_calc"] = clean["desercion_pct"].fillna((clean["desertores"] / clean["existencia"]) * 100)
    # Limitamos porcentajes negativos para evitar ruido en reportes con ajustes administrativos.
    clean["desercion_pct_calc"] = clean["desercion_pct_calc"].clip(lower=0)
    # Acotamos eficiencia terminal a 0-100 para lectura publica.
    clean["eficiencia_terminal_pct_calc"] = clean["eficiencia_terminal_pct"].clip(lower=0, upper=100)
    # Calculamos un indice simple de riesgo explicable para exposicion.
    clean["riesgo_score"] = (
        clean["reprobacion_pct_calc"].fillna(0) * 2.2
        + clean["desercion_pct_calc"].fillna(0) * 3.0
        + (100 - clean["eficiencia_terminal_pct_calc"].fillna(100)).clip(lower=0) * 0.7
    ).clip(0, 100)
    # Clasificamos el riesgo para visualizacion.
    clean["riesgo_nivel"] = pd.cut(clean["riesgo_score"], bins=[-1, 20, 40, 100], labels=["Bajo", "Medio", "Alto"])
    # Guardamos de que archivo salio cada fila para trazabilidad.
    clean["archivo_origen"] = str(path.relative_to(PROJECT_ROOT))
    # Regresamos el dataset limpio del archivo actual.
    return clean


def weighted_average(frame: pd.DataFrame, value_column: str, weight_column: str = "existencia") -> float | None:
    # Quitamos filas sin valor o sin peso.
    valid = frame[[value_column, weight_column]].dropna()
    # Si no hay datos validos, regresamos None para que llegue como null.
    if valid.empty or valid[weight_column].sum() == 0:
        return None
    # Calculamos promedio ponderado por matricula.
    return float(np.average(valid[value_column], weights=valid[weight_column]))


def safe_round(value, digits: int = 2) -> float | None:
    # Si no hay valor, lo dejamos como None para que JSON lo guarde como null.
    if value is None:
        return None
    # Si pandas o numpy mandan NaN, tambien lo dejamos como null.
    if isinstance(value, (float, np.floating)) and math.isnan(float(value)):
        return None
    # Redondeamos solo cuando existe un numero real.
    return round(float(value), digits)


def records_without_nan(frame: pd.DataFrame) -> list[dict]:
    # Redondeamos numeros para que la vista sea amable.
    rounded = frame.round(2)
    # Cambiamos NaN por None para que PHP reciba null, no valores raros.
    cleaned = rounded.astype(object).where(pd.notnull(rounded), None)
    # Convertimos la tabla a lista de diccionarios lista para JSON.
    return cleaned.to_dict(orient="records")


def aggregate_period_level(clean: pd.DataFrame) -> pd.DataFrame:
    # Agrupamos por ciclo escolar y nivel educativo.
    grouped = clean.groupby(["periodo", "nivel_reporte"], as_index=False)
    # Calculamos metricas principales de cada grupo.
    summary = grouped.apply(
        lambda frame: pd.Series(
            {
                "escuelas": int(frame["escuela"].count()),
                "matricula": int(frame["existencia"].sum()),
                "reprobados": int(frame["reprobados"].fillna(0).sum()),
                "desertores": int(frame["desertores"].fillna(0).sum()),
                "reprobacion_pct": weighted_average(frame, "reprobacion_pct_calc"),
                "desercion_pct": weighted_average(frame, "desercion_pct_calc"),
                "eficiencia_terminal_pct": weighted_average(frame, "eficiencia_terminal_pct_calc"),
                "riesgo_score": weighted_average(frame, "riesgo_score"),
            }
        )
    )
    # Quitamos el indice extra que genera groupby.apply.
    summary = summary.reset_index(drop=True)
    # Regresamos el resumen listo para graficar.
    return summary


def aggregate_municipality(clean: pd.DataFrame) -> pd.DataFrame:
    # Creamos una lista para evitar cambios raros de groupby.apply entre versiones.
    rows: list[dict] = []
    # Agrupamos por municipio normalizado y conservamos el nombre del grupo.
    for municipality_name, frame in clean.groupby("municipio_normalizado"):
        # Agregamos una fila resumida por municipio.
        rows.append(
            {
                "municipio_normalizado": municipality_name,
                "municipio": str(municipality_name).title(),
                "escuelas": int(frame["escuela"].count()),
                "matricula": int(frame["existencia"].sum()),
                "reprobacion_pct": weighted_average(frame, "reprobacion_pct_calc"),
                "desercion_pct": weighted_average(frame, "desercion_pct_calc"),
                "riesgo_score": weighted_average(frame, "riesgo_score"),
            }
        )
    # Convertimos la lista a DataFrame para poder ordenar y graficar.
    summary = pd.DataFrame(rows)
    # Ordenamos por mayor riesgo para hacer visible la prioridad.
    return summary.sort_values("riesgo_score", ascending=False)


def save_chart_trend(period_summary: pd.DataFrame) -> str:
    # Definimos la ruta de salida de la grafica.
    output = CHART_DIR / "riesgo_por_periodo.png"
    # Creamos una figura amplia para barras agrupadas.
    fig, ax = plt.subplots(figsize=(10, 5.2))
    # Tomamos los periodos ordenados.
    periods = sorted(period_summary["periodo"].unique())
    # Definimos la posicion base de cada periodo.
    x = np.arange(len(periods))
    # Definimos el ancho de cada barra.
    width = 0.36
    # Recorremos niveles para dibujar barras por separado.
    for offset, level in [(-width / 2, "Secundaria"), (width / 2, "Media superior")]:
        # Filtramos el nivel actual.
        data = period_summary[period_summary["nivel_reporte"] == level].set_index("periodo").reindex(periods)
        # Dibujamos las barras.
        ax.bar(x + offset, data["riesgo_score"], width, label=level)
    # Agregamos titulo claro.
    ax.set_title("Indice de riesgo academico por ciclo escolar")
    # Agregamos etiqueta vertical.
    ax.set_ylabel("Riesgo ponderado (0-100)")
    # Colocamos las etiquetas de periodos.
    ax.set_xticks(x, periods, rotation=0)
    # Limitamos el eje de riesgo.
    ax.set_ylim(0, 100)
    # Agregamos cuadricula suave.
    ax.grid(axis="y", alpha=0.18)
    # Agregamos leyenda.
    ax.legend()
    # Ajustamos espacios para que no se corte el texto.
    fig.tight_layout()
    # Guardamos la imagen.
    fig.savefig(output, dpi=150, transparent=True)
    # Cerramos la figura para liberar memoria.
    plt.close(fig)
    # Regresamos la ruta relativa para PHP.
    return str(output.relative_to(PROJECT_ROOT))


def save_chart_factors(latest_summary: pd.DataFrame) -> str:
    # Definimos la ruta de salida.
    output = CHART_DIR / "factores_ultimo_periodo.png"
    # Creamos la figura.
    fig, ax = plt.subplots(figsize=(8, 5))
    # Creamos etiquetas con el nivel educativo.
    labels = latest_summary["nivel_reporte"].tolist()
    # Armamos posiciones de barras.
    x = np.arange(len(labels))
    # Definimos ancho de barra.
    width = 0.25
    # Dibujamos reprobacion.
    ax.bar(x - width, latest_summary["reprobacion_pct"], width, label="Reprobacion")
    # Dibujamos desercion.
    ax.bar(x, latest_summary["desercion_pct"], width, label="Desercion")
    # Dibujamos brecha de eficiencia.
    ax.bar(x + width, (100 - latest_summary["eficiencia_terminal_pct"].fillna(100)).clip(lower=0), width, label="Brecha eficiencia")
    # Agregamos titulo.
    ax.set_title("Factores de riesgo en el ultimo ciclo")
    # Agregamos etiqueta vertical.
    ax.set_ylabel("Porcentaje ponderado")
    # Colocamos etiquetas del eje x.
    ax.set_xticks(x, labels)
    # Agregamos cuadricula discreta.
    ax.grid(axis="y", alpha=0.18)
    # Agregamos leyenda.
    ax.legend()
    # Ajustamos layout.
    fig.tight_layout()
    # Guardamos la grafica.
    fig.savefig(output, dpi=150, transparent=True)
    # Cerramos figura.
    plt.close(fig)
    # Regresamos ruta relativa.
    return str(output.relative_to(PROJECT_ROOT))


def save_chart_margin(clean: pd.DataFrame) -> str:
    # Definimos la ruta de salida.
    output = CHART_DIR / "marginacion_pastel.png"
    # Calculamos distribucion por marginacion.
    counts = clean["marginacion"].dropna().value_counts().head(6)
    # Creamos figura.
    fig, ax = plt.subplots(figsize=(6.5, 6.5))
    # Dibujamos grafica de pastel.
    ax.pie(counts.values, labels=counts.index, autopct="%1.1f%%", startangle=90)
    # Agregamos titulo.
    ax.set_title("Escuelas por grado de marginacion")
    # Ajustamos layout.
    fig.tight_layout()
    # Guardamos imagen.
    fig.savefig(output, dpi=150, transparent=True)
    # Cerramos figura.
    plt.close(fig)
    # Regresamos ruta relativa.
    return str(output.relative_to(PROJECT_ROOT))


def save_chart_map(municipality_summary: pd.DataFrame) -> str:
    # Definimos la ruta de salida.
    output = CHART_DIR / "mapa_riesgo_municipal.png"
    # Copiamos el resumen para agregar coordenadas.
    data = municipality_summary.copy()
    # Agregamos latitud desde el diccionario.
    data["lat"] = data["municipio_normalizado"].map(lambda name: MUNICIPALITY_COORDS.get(name, (np.nan, np.nan))[0])
    # Agregamos longitud desde el diccionario.
    data["lon"] = data["municipio_normalizado"].map(lambda name: MUNICIPALITY_COORDS.get(name, (np.nan, np.nan))[1])
    # Quitamos municipios sin coordenadas.
    data = data.dropna(subset=["lat", "lon"])
    # Creamos figura.
    fig, ax = plt.subplots(figsize=(8, 6.5))
    # Calculamos tamano de burbuja por matricula.
    sizes = np.sqrt(data["matricula"].clip(lower=1)) * 8
    # Dibujamos burbujas coloreadas por riesgo.
    scatter = ax.scatter(data["lon"], data["lat"], s=sizes, c=data["riesgo_score"], cmap="coolwarm", alpha=0.78, edgecolor="white", linewidth=0.8)
    # Etiquetamos los municipios con mayor riesgo.
    for _, row in data.head(8).iterrows():
        ax.text(row["lon"] + 0.015, row["lat"] + 0.015, row["municipio"], fontsize=8)
    # Agregamos titulo.
    ax.set_title("Mapa municipal de riesgo academico en Queretaro")
    # Quitamos etiqueta tecnica del eje x.
    ax.set_xlabel("Longitud")
    # Quitamos etiqueta tecnica del eje y.
    ax.set_ylabel("Latitud")
    # Agregamos barra de color.
    fig.colorbar(scatter, ax=ax, label="Riesgo ponderado")
    # Agregamos cuadricula suave.
    ax.grid(alpha=0.15)
    # Ajustamos layout.
    fig.tight_layout()
    # Guardamos imagen.
    fig.savefig(output, dpi=150, transparent=True)
    # Cerramos figura.
    plt.close(fig)
    # Regresamos ruta relativa.
    return str(output.relative_to(PROJECT_ROOT))


def build_summary(clean: pd.DataFrame, period_summary: pd.DataFrame, municipality_summary: pd.DataFrame, charts: dict[str, str]) -> dict:
    # Tomamos el ultimo periodo disponible.
    latest_period = sorted(clean["periodo"].unique())[-1]
    # Filtramos el ultimo periodo.
    latest = clean[clean["periodo"] == latest_period]
    # Calculamos valores ponderados del ultimo ciclo con manejo de null.
    latest_risk = weighted_average(latest, "riesgo_score")
    # Calculamos reprobacion ponderada del ultimo ciclo.
    latest_reprobation = weighted_average(latest, "reprobacion_pct_calc")
    # Calculamos desercion ponderada del ultimo ciclo.
    latest_dropout = weighted_average(latest, "desercion_pct_calc")
    # Calculamos tarjetas principales.
    kpis = {
        "periodo_inicial": sorted(clean["periodo"].unique())[0],
        "periodo_final": latest_period,
        "archivos": int(clean["archivo_origen"].nunique()),
        "registros": int(len(clean)),
        "escuelas_unicas": int(clean["escuela"].nunique()),
        "municipios": int(clean["municipio_normalizado"].nunique()),
        "matricula_ultimo_periodo": int(latest["existencia"].sum()),
        "riesgo_ultimo_periodo": safe_round(latest_risk),
        "reprobacion_ultimo_periodo": safe_round(latest_reprobation),
        "desercion_ultimo_periodo": safe_round(latest_dropout),
    }
    # Tomamos municipios prioritarios.
    top_municipalities = records_without_nan(municipality_summary.head(8))
    # Tomamos tabla por periodo.
    period_rows = records_without_nan(period_summary)
    # Armamos el diccionario final.
    return {
        "generated_at": pd.Timestamp.now().isoformat(),
        "source": "Reportes oficiales de Indicadores de Eficiencia Educativa, Queretaro",
        "kpis": kpis,
        "charts": charts,
        "period_summary": period_rows,
        "top_municipalities": top_municipalities,
    }


def write_notebook() -> None:
    # Creamos celdas del notebook como estructura JSON.
    cells = [
        {
            "cell_type": "markdown",
            "metadata": {},
            "source": ["# Analisis de datasets oficiales ATENEA\\n", "\\n", "Este notebook documenta el procesamiento de archivos XLSX reales de eficiencia educativa para explicar riesgo academico dentro del enfoque ODS 4."],
        },
        {
            "cell_type": "code",
            "execution_count": None,
            "metadata": {},
            "outputs": [],
            "source": ["import pandas as pd\\n", "import matplotlib.pyplot as plt\\n", "from pathlib import Path\\n", "\\n", "clean = pd.read_csv('DATASETS/processed/official_education_clean.csv')\\n", "clean.head()"],
        },
        {
            "cell_type": "markdown",
            "metadata": {},
            "source": ["## Ciclo de vida de datos\\n", "\\n", "1. Ingesta de XLSX oficiales.\\n", "2. Limpieza de encabezados combinados.\\n", "3. Normalizacion de columnas clave.\\n", "4. Calculo de indicadores ponderados.\\n", "5. Visualizacion para toma de decisiones educativas."],
        },
        {
            "cell_type": "code",
            "execution_count": None,
            "metadata": {},
            "outputs": [],
            "source": ["summary = clean.groupby(['periodo', 'nivel_reporte']).agg(\\n", "    escuelas=('escuela', 'count'),\\n", "    matricula=('existencia', 'sum'),\\n", "    riesgo=('riesgo_score', 'mean'),\\n", "    reprobacion=('reprobacion_pct_calc', 'mean'),\\n", "    desercion=('desercion_pct_calc', 'mean')\\n", ").reset_index()\\n", "summary"],
        },
        {
            "cell_type": "code",
            "execution_count": None,
            "metadata": {},
            "outputs": [],
            "source": ["pivot = summary.pivot(index='periodo', columns='nivel_reporte', values='riesgo')\\n", "pivot.plot(kind='bar', figsize=(10, 5), title='Riesgo academico por periodo')\\n", "plt.ylabel('Riesgo promedio')\\n", "plt.tight_layout()\\n", "plt.show()"],
        },
        {
            "cell_type": "code",
            "execution_count": None,
            "metadata": {},
            "outputs": [],
            "source": ["clean['marginacion'].value_counts().head(6).plot(kind='pie', autopct='%1.1f%%', figsize=(6, 6), title='Distribucion por marginacion')\\n", "plt.ylabel('')\\n", "plt.tight_layout()\\n", "plt.show()"],
        },
    ]
    # Creamos el documento notebook completo.
    notebook = {
        "cells": cells,
        "metadata": {
            "kernelspec": {"display_name": "Python 3", "language": "python", "name": "python3"},
            "language_info": {"name": "python", "pygments_lexer": "ipython3"},
        },
        "nbformat": 4,
        "nbformat_minor": 5,
    }
    # Convertimos secuencias "\\n" a saltos reales para que Jupyter ejecute bien.
    for cell in notebook["cells"]:
        # Normalizamos cada linea de la celda.
        cell["source"] = [line.replace("\\\\n", "\n") for line in cell["source"]]
    # Guardamos el notebook con indentacion legible.
    NOTEBOOK_FILE.write_text(json.dumps(notebook, indent=2, ensure_ascii=False), encoding="utf-8")


def main() -> None:
    # Creamos carpetas de salida si no existen.
    CHART_DIR.mkdir(parents=True, exist_ok=True)
    # Buscamos todos los XLSX reales ignorando locks temporales.
    files = sorted(path for path in DATASET_DIR.glob("*/*.xlsx") if not path.name.startswith(".~lock"))
    # Detenemos el proceso si no hay archivos.
    if not files:
        raise SystemExit("No se encontraron archivos XLSX en DATASETS.")
    # Procesamos cada workbook y guardamos los resultados.
    frames = [parse_workbook(path) for path in files]
    # Unimos todos los periodos en un solo dataset limpio.
    clean = pd.concat(frames, ignore_index=True)
    # Guardamos el dataset limpio en CSV.
    clean.to_csv(CLEAN_CSV, index=False)
    # Calculamos resumen por periodo y nivel.
    period_summary = aggregate_period_level(clean)
    # Calculamos resumen municipal.
    municipality_summary = aggregate_municipality(clean)
    # Tomamos el ultimo periodo para la grafica de factores.
    latest_period = sorted(period_summary["periodo"].unique())[-1]
    # Filtramos el resumen del ultimo periodo.
    latest_summary = period_summary[period_summary["periodo"] == latest_period]
    # Generamos todas las graficas.
    charts = {
        "trend": save_chart_trend(period_summary),
        "factors": save_chart_factors(latest_summary),
        "margin": save_chart_margin(clean),
        "map": save_chart_map(municipality_summary),
    }
    # Armamos el JSON para el LandingPage.
    summary = build_summary(clean, period_summary, municipality_summary, charts)
    # Guardamos el JSON con acentos permitidos para la vista.
    SUMMARY_JSON.write_text(json.dumps(summary, indent=2, ensure_ascii=False, allow_nan=False), encoding="utf-8")
    # Creamos el notebook solicitado.
    write_notebook()
    # Mostramos un resumen de salida para terminal.
    print(json.dumps({"ok": True, "files": len(files), "rows": len(clean), "output": str(SUMMARY_JSON)}, ensure_ascii=False))


# Ejecutamos main solo cuando se llama el archivo directamente.
if __name__ == "__main__":
    main()
