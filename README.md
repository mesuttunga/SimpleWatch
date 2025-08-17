# SimpleWatch

Production-ready uptime monitoring application deployed on AWS EKS with Kubernetes.

![Status](https://img.shields.io/badge/status-production-green)
![Kubernetes](https://img.shields.io/badge/kubernetes-1.34-blue)
![AWS](https://img.shields.io/badge/AWS-EKS-orange)

## 🏗️ Architecture
```
Internet
    ↓
AWS ELB (LoadBalancer)
    ↓
┌─────────────────────────────────┐
│   EKS Cluster (2x t3.small)     │
├─────────────────────────────────┤
│  PHP Pods (2 replicas)          │
│  └─ Liveness/Readiness probes   │
│                                 │
│  MySQL StatefulSet (1 replica)  │
│  └─ 5GB EBS Persistent Volume   │
└─────────────────────────────────┘
```

## ✨ Features

- **High Availability**: 2 PHP pod replicas across multiple nodes
- **Persistent Storage**: MySQL with EBS-backed persistent volumes
- **Auto-healing**: Liveness probes with automatic pod restart
- **Load Balancing**: AWS ELB distributing traffic across pods
- **Configuration Management**: ConfigMap for environment variables
- **Secret Management**: Kubernetes Secrets for sensitive data
- **Resource Management**: CPU/Memory limits and requests
- **Production-tested**: Running on AWS EKS

## 📋 Prerequisites

- AWS CLI configured with credentials
- kubectl CLI
- Terraform >= 1.0
- eksctl (for EBS CSI driver setup)
- Docker (for building images)

## 🚀 Quick Start

### 1. Infrastructure Setup (Terraform)
```bash
cd terraform/eks

# Initialize Terraform
terraform init

# Review plan
terraform plan

# Create EKS cluster (~15 minutes)
terraform apply

# Configure kubectl
aws eks update-kubeconfig --region eu-west-2 --name simplewatch-eks
```

### 2. EBS CSI Driver Setup
```bash
# Create OIDC provider
eksctl utils associate-iam-oidc-provider \
  --region=eu-west-2 \
  --cluster=simplewatch-eks \
  --approve

# Create IAM service account
eksctl create iamserviceaccount \
  --name ebs-csi-controller-sa \
  --namespace kube-system \
  --cluster simplewatch-eks \
  --region eu-west-2 \
  --role-name AmazonEKS_EBS_CSI_DriverRole \
  --attach-policy-arn arn:aws:iam::aws:policy/service-role/AmazonEBSCSIDriverPolicy \
  --approve

# Install EBS CSI driver addon
eksctl create addon \
  --name aws-ebs-csi-driver \
  --cluster simplewatch-eks \
  --region eu-west-2 \
  --service-account-role-arn arn:aws:iam::YOUR_ACCOUNT_ID:role/AmazonEKS_EBS_CSI_DriverRole \
  --force
```

### 3. Application Deployment
```bash
# Deploy all resources
kubectl apply -f k8s/namespace.yaml
kubectl apply -f k8s/configmap.yaml
kubectl apply -f k8s/secret.yaml
kubectl apply -f k8s/mysql-statefulset.yaml
kubectl apply -f k8s/php-deployment.yaml
kubectl apply -f k8s/service.yaml

# Check deployment status
kubectl get all,pvc -n simplewatch

# Get LoadBalancer URL
kubectl get service simplewatch-service -n simplewatch
```

## 📂 Project Structure
```
simplewatch/
├── app/
│   ├── Dockerfile                 # Production PHP container
│   └── public/
│       └── index.php              # Dashboard application
├── k8s/
│   ├── namespace.yaml             # Kubernetes namespace
│   ├── configmap.yaml             # Environment configuration
│   ├── secret.yaml                # Sensitive data (gitignored)
│   ├── mysql-statefulset.yaml     # MySQL with persistent storage
│   ├── php-deployment.yaml        # PHP app with health checks
│   └── service.yaml               # LoadBalancer service
├── terraform/
│   └── eks/
│       ├── provider.tf            # AWS provider configuration
│       ├── variables.tf           # Input variables
│       ├── main.tf                # EKS cluster definition
│       └── outputs.tf             # Cluster outputs
└── README.md
```

## 🔧 Configuration

### ConfigMap (k8s/configmap.yaml)
```yaml
APP_NAME: "SimpleWatch Production"
CHECK_INTERVAL: "60"
DB_HOST: "mysql-service"
```

### Secret (k8s/secret.yaml)
Create your own secret:
```bash
echo -n "YourPassword" | base64
# Add to k8s/secret.yaml
```

## 🧪 Testing

### Health Checks
```bash
# Check pod health
kubectl get pods -n simplewatch

# Test liveness probe
kubectl describe pod <php-pod-name> -n simplewatch

# Check logs
kubectl logs -f <pod-name> -n simplewatch
```

### Load Balancer
```bash
# Get URL
URL=$(kubectl get service simplewatch-service -n simplewatch -o jsonpath='{.status.loadBalancer.ingress[0].hostname}')

# Test endpoint
curl http://$URL
```

## 💰 Cost Estimation

- **EKS Control Plane**: ~$73/month
- **2x t3.small nodes**: ~$30/month
- **5GB EBS gp2**: ~$0.50/month
- **ELB**: ~$18/month
- **Data transfer**: Variable

**Total**: ~$120-130/month

## 🧹 Cleanup

### Delete Application
```bash
kubectl delete namespace simplewatch
```

### Destroy Infrastructure
```bash
cd terraform/eks
terraform destroy
```

## 🔍 Troubleshooting

### MySQL Pod Pending
- Check PVC status: `kubectl get pvc -n simplewatch`
- Verify EBS CSI driver: `eksctl get addon --cluster simplewatch-eks`
- Check events: `kubectl get events -n simplewatch`

### LoadBalancer Pending
- Check AWS ELB creation: AWS Console → EC2 → Load Balancers
- Verify security groups allow port 80

### ConfigMap Not Found
- Ensure namespace exists first
- Apply in order: namespace → configmap → deployment

## 📚 Learn More

- **Kubernetes**: https://kubernetes.io/docs/
- **AWS EKS**: https://docs.aws.amazon.com/eks/
- **Terraform**: https://www.terraform.io/docs/

## 🎯 Production URL

**Live Demo**: http://a9e3b14dd8f9f4a74ae015c2226c888a-1717702545.eu-west-2.elb.amazonaws.com

## 📝 License

MIT

---

**Built with ❤️ for production Kubernetes environments**