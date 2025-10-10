FROM python:3.11-slim

RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    wget \
    curl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /workspace

COPY tests/ui/requirements.txt /workspace/tests/ui/requirements.txt

RUN pip install --upgrade pip
RUN pip install -r /workspace/tests/ui/requirements.txt

CMD ["tail", "-f", "/dev/null"]
