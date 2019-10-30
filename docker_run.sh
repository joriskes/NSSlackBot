#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# Run
docker run \
    --name nsbot \
    -d \
    nsbot

