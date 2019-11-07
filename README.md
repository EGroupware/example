# Example app for development

### 1. Step: minimal "Hello World" app

![step1-hello-world](https://user-images.githubusercontent.com/972180/68392090-4b047a80-0169-11ea-87ce-a1ef6d2f7ea5.png)

This branch shows the minimal directory and file structure for an EGroupware app.

1. Cloning the repo into you EGroupware directory:
```
cd /path/to/egroupware
git clone -b step1 https://github.com/EGroupware/example.git
```

2. Install the app in EGroupware setup: http://localhost/egroupware/setup/

* Log in using the "admin" account for setup in the upper login box
![step1-setup](https://user-images.githubusercontent.com/972180/68393526-85234b80-016c-11ea-8480-620160c3b086.png)
* click on Application and check install checkbox for example
![step1-application-install](https://user-images.githubusercontent.com/972180/68393525-85234b80-016c-11ea-9677-8c535ac03a15.png)
* Log into EGroupware again and add run-rights for example app to eg. default group
![step1-admin-acl](https://user-images.githubusercontent.com/972180/68394226-b9e3d280-016d-11ea-9dbf-e769eb499e82.png)
![step1-add-run-acl](https://user-images.githubusercontent.com/972180/68394225-b9e3d280-016d-11ea-856d-36033d6c7fd8.png)
* you have to log out and in again, so you session get's the new application run rights

--> [continue to step 2](https://github.com/EGroupware/example/tree/step2) by checking out branch ```step2``` in your workingcopy
