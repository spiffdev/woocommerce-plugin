#!/usr/bin/groovy

@Library('github.com/iag-abd/pipeline-shared-library') _

pipeline {
  agent any
  options {
    skipDefaultCheckout true
  }

  environment {
    SERVICE = 'woocommerce-plugin'
  }

  stages {
    stage('Dummy stage') {
      steps {
        print 'This build is in progress.'
      }
    }
  }

  post {
    success {
      script {
        local_date = new Date().format('yyyy-MM-dd hh:mm')
        slack_text = """
          ${SERVICE} finished and successful at ${local_date}
          """
        slackSend color: "#0bab47", message: slack_text
      }
    }
    unstable {
      slackSend color: "#FFF000", message: "${SERVICE} unstable in ${env.JOB_URL}"
    }
    failure {
      slackSend color: "#FF0000", message: "${SERVICE} failed in ${env.JOB_URL}"
    }
  }
}
