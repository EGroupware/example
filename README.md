# Example app for EGroupware development

#### 1. Step: minimal "Hello World" app

![step1-hello-world](https://raw.githubusercontent.com/wiki/EGroupware/example/images/step1-01.png)

This branch shows the minimal directory and file structure for an EGroupware app.

1. Cloning the repo into you EGroupware directory:
```
cd /path/to/egroupware
git clone -b step1 https://github.com/EGroupware/example.git
```

2. Install the app in EGroupware setup: http://localhost/egroupware/setup/

* Log in using the "admin" account for setup in the upper login box
![step1-setup](https://raw.githubusercontent.com/wiki/EGroupware/example/images/step1-02.png)
* click on Application and check install checkbox for example
![step1-application-install](https://raw.githubusercontent.com/wiki/EGroupware/example/images/step1-03.png)
* Log into EGroupware again and add run-rights for example app to eg. default group
![step1-admin-acl](https://raw.githubusercontent.com/wiki/EGroupware/example/images/step1-04.png)
![step1-add-run-acl](https://raw.githubusercontent.com/wiki/EGroupware/example/images/step1-05.png)
* you have to log out and in again, so you session get's the new application run rights

--> [continue to step 2](https://github.com/EGroupware/example/tree/step2) by checking out branch ```step2``` in your workingcopy
