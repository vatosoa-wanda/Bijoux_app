# Remplacez apache/airflow:2.10.4 par la version exacte indiquée en haut de
# votre docker-compose.yaml (variable AIRFLOW_IMAGE_NAME) : l'image
# personnalisée doit partir de la même version que celle prévue par le
# compose officiel, sinon les autres services (scheduler, worker...)
# resteront sur une version différente de celle du webserver.
FROM apache/airflow:3.3.1

COPY requirements-airflow.txt /requirements.txt
RUN pip install --no-cache-dir -r /requirements.txt