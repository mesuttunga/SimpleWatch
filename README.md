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
- **CI/CD Pipeline**: Automated build and deployment with GitHub Actions
- **Container Registry**: GitHub Container Registry (GHCR) for image storage
- **Automatic Rollback**: Failed deployments automatically revert to previous version
- **Health Checks**: Automated verification of pod and application health
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

### 3. GitHub Actions CI/CD Setup

#### Configure Repository Secrets
```bash
# Using GitHub CLI
gh secret set AWS_ACCESS_KEY_ID
gh secret set AWS_SECRET_ACCESS_KEY
gh secret set DB_PASSWORD

# Or via GitHub UI: Settings → Secrets and variables → Actions
```

#### Configure Repository Variables
```bash
# Start with deployments disabled for safety
gh variable set ENABLE_AWS_DEPLOY --body "false"

# Or via GitHub UI: Settings → Secrets and variables → Actions → Variables
```

#### Enable Image Pull from GHCR
Make sure your repository is public, or update `php-deployment.yaml` to include:
```yaml
imagePullSecrets:
  - name: ghcr-secret
```

### 4. Manual First Deployment (Recommended)
```bash
# Deploy all resources manually first
kubectl apply -f k8s/namespace.yaml
kubectl apply -f k8s/configmap.yaml

# Create secret manually (will be managed by GitHub Actions later)
kubectl create secret generic simplewatch-secret \
  --from-literal=DB_PASSWORD='YourStrongPassword' \
  --namespace=simplewatch

# Deploy database and application
kubectl apply -f k8s/mysql-statefulset.yaml
kubectl apply -f k8s/php-deployment.yaml
kubectl apply -f k8s/service.yaml

# Check deployment status
kubectl get all,pvc -n simplewatch

# Get LoadBalancer URL
kubectl get service simplewatch-service -n simplewatch
```

### 5. Enable Automated CI/CD
```bash
# Once infrastructure is stable, enable automated deployments
gh variable set ENABLE_AWS_DEPLOY --body "true"

# Now every push to main will trigger:
# 1. Docker build → GitHub Container Registry
# 2. Deployment to EKS with health checks
# 3. Automatic rollback on failure

git add .
git commit -m "Enable CI/CD"
git push origin main
```

## 📂 Project Structure
```
simplewatch/
├── .github/
│   └── workflows/
│       └── deploy.yml             # CI/CD pipeline
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

## 🔄 CI/CD Pipeline

The project uses GitHub Actions for automated build and deployment:

### Pipeline Flow
1. **Build Stage** (Always runs)
   - Checkout code
   - Build Docker image with BuildKit cache
   - Push to GitHub Container Registry (ghcr.io)
   - Tag with: `latest`, `short-sha`, `timestamp`

2. **Deploy Stage** (When `ENABLE_AWS_DEPLOY=true`)
   - Configure AWS credentials
   - Update kubeconfig for EKS
   - Create/update Kubernetes secrets
   - Deploy all manifests
   - Update deployment image to new SHA
   - Wait for rollout (5min timeout)
   - Health checks on pods
   - **Automatic rollback on failure**

### GitHub Secrets Required
```bash
AWS_ACCESS_KEY_ID       # AWS credentials for EKS access
AWS_SECRET_ACCESS_KEY   # AWS credentials for EKS access
DB_PASSWORD             # MySQL root password
GITHUB_TOKEN            # Auto-provided by GitHub Actions
```

### GitHub Variables
```bash
ENABLE_AWS_DEPLOY       # "true" or "false" - controls deployment
```

### Monitoring Deployments
```bash
# Watch GitHub Actions
# Go to: Repository → Actions tab

# Or via CLI
gh run list
gh run watch

# Check deployment status
kubectl get pods -n simplewatch -w
kubectl rollout status deployment/simplewatch-php -n simplewatch
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

### GitHub Actions Deployment Fails
- **Check secrets**: Ensure `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `DB_PASSWORD` are set
- **Check variable**: Verify `ENABLE_AWS_DEPLOY` is set to "true"
- **AWS credentials**: Test with `aws eks list-clusters` locally
- **Image pull**: For private repos, ensure `imagePullSecrets` is configured
- **View logs**: GitHub Actions → Failed run → Deploy step logs

### Image Pull Errors
```bash
# Check if GHCR secret exists
kubectl get secret ghcr-secret -n simplewatch

# Recreate manually if needed
kubectl create secret docker-registry ghcr-secret \
  --docker-server=ghcr.io \
  --docker-username=YOUR_GITHUB_USERNAME \
  --docker-password=YOUR_GITHUB_TOKEN \
  --namespace=simplewatch

# Verify image exists
# Go to: GitHub repo → Packages → simplewatch
```

### Rollback to Previous Version
```bash
# View rollout history
kubectl rollout history deployment/simplewatch-php -n simplewatch

# Rollback to previous version
kubectl rollout undo deployment/simplewatch-php -n simplewatch

# Rollback to specific revision
kubectl rollout undo deployment/simplewatch-php -n simplewatch --to-revision=2
```

## 📚 Learn More

- **Kubernetes**: https://kubernetes.io/docs/
- **AWS EKS**: https://docs.aws.amazon.com/eks/
- **Terraform**: https://www.terraform.io/docs/

## 📝 License

MIT


---

**Built with ❤️ for production Kubernetes environments**