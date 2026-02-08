FROM python:3.10-slim

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        curl \
        libgl1 \
        libglib2.0-0 \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /comfyui

RUN git clone https://github.com/comfyanonymous/ComfyUI.git /comfyui

ENV PIP_DEFAULT_TIMEOUT=300

RUN pip install --no-cache-dir --retries 5 -r /comfyui/requirements.txt

EXPOSE 8188

CMD ["python", "main.py", "--listen", "0.0.0.0", "--port", "8188"]
