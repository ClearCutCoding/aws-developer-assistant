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
- Now you can update your IP in AWS.  If you do not pass in `--project` it will update for all projects

```
aws-developer-assistant security-group:cidr --project myproject
```

## Create PHAR file

```
bin/box-compile
build/aws-developer-assistant security-group:cidr --project myproject
```

### Resources

- https://betterprogramming.pub/a-step-by-step-guide-to-create-homebrew-taps-from-github-repos-f33d3755ba74
- https://medium.com/@cyrilgeorgespereira/create-a-compiled-php-console-application-and-deploy-it-with-homebrew-93876a8540fb
- https://github.com/box-project/box/blob/main/doc/symfony.md#project-directory

