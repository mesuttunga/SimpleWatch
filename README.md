# SimpleWatch

Lightweight uptime monitoring for APIs and websites, designed to run on Kubernetes.

## Features
- Health check monitoring for HTTP/HTTPS endpoints
- Kubernetes-native deployment
- Scalable architecture with replicas
- Easy deployment with YAML manifests

## Quick Start
```bash
kubectl apply -f deployment.yaml
kubectl apply -f service.yaml
```

## Architecture
- Nginx-based frontend
- Kubernetes Deployment for high availability
- NodePort service for external access

## Requirements
- Kubernetes cluster (minikube/EKS/AKS)
- kubectl CLI

---
Built for production Kubernetes environments.