# AWS Developer Assistant

- Ensure the `aws` cli is installed and configured
- Create a config file at `~/.clearcutcoding/aws-developer-assistant/config.yaml`

```
security-group-cidr:
  cidr.save_path: ~/.aws/cidr
  projects:
    myproject:
      aws.profile: myproject-personal
      security_groups:
        - region: eu-west-1
          id: saas-master-EcsSecurityGroup-xxxxxx
          port: 22
        - region: eu-west-2
          id: saas-db-DBSecurityGroup-xxxxxx
          port: 3306
```