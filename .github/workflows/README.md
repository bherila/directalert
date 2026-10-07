# GitHub Actions Deployment

This directory contains the GitHub Actions workflow for deploying the application.

## Setup

This workflow uses a GitHub Environment named `prod`. You will need to create this environment in your repository settings (`Settings > Environments > New environment`).

Once the `prod` environment is created, you need to configure the following secrets as **Environment secrets** within that environment:

1.  `SSH_HOST`: The hostname or IP address of the deployment server.
    -   Example: `your.server.com`

2.  `SSH_USERNAME`: The username for SSH login.
    -   Example: `your_user`

3.  `SSH_PASSWORD`: The password for the SSH user.

4.  `SSH_KNOWN_HOSTS`: The deployment server's public host keys, in `known_hosts` format. Every connection pins them (`StrictHostKeyChecking=yes`), and the deploy fails before connecting if this secret is empty.
    -   Generate with `ssh-keyscan -t ed25519,ecdsa,rsa <host>`, check the fingerprints against a connection you already trust, and make sure each line names the same host or IP as `SSH_HOST`.

## Deployment Target

The workflow deploys the application to the `~/bwh-php/` directory on the remote server. Make sure this directory exists and the specified SSH user has write permissions to it.
