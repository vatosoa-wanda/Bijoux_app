from datetime import datetime, timedelta

from airflow import DAG
from airflow.operators.python import PythonOperator

from airflow_tasks import extract_task, load_task, transform_task

default_args = {
    "owner": "wanda",
    "retries": 2,
    "retry_delay": timedelta(minutes=5),
}

with DAG(
    dag_id="bijoux_pipeline",
    description="Pipeline data bijoux : ingestion PostgreSQL -> BigQuery -> agrégation",
    default_args=default_args,
    schedule="0 2 * * *",        # tous les jours à 2h, comme le cron précédent
    start_date=datetime(2026, 9, 1),
    catchup=False,                # ne pas rejouer les jours passés au premier lancement
    tags=["bijoux", "data-platform"],
) as dag:

    extract = PythonOperator(
        task_id="extract_postgresql",
        python_callable=extract_task,
    )

    load = PythonOperator(
        task_id="load_bigquery",
        python_callable=load_task,
    )

    transform = PythonOperator(
        task_id="transform_aggregate",
        python_callable=transform_task,
    )

    extract >> load >> transform