#!groovy

@Library('platform-jenkins-pipeline') _

pipeline {
    agent none

    stages {
        stage('Build Module') {
            steps {
                buildModule('magento-module')
            }
        }
    }

    post {
        always {
            sendNotifications()
        }
    }
}