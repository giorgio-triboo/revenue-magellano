#!/bin/bash
# Prepara la directory di destinazione prima della copia (fase Install).
# Deploy sotto ec2-user così l'agent può scrivere e ec2-user può eseguire docker.
set -e
DEST="/home/ec2-user/revenue.magellano.ai"
mkdir -p "$DEST"
chown ec2-user:ec2-user "$DEST"
chmod 755 "$DEST"
echo "Destination $DEST ready for Install phase"
