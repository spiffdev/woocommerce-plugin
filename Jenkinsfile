#!/usr/bin/groovy

@Library('github.com/iag-abd/pipeline-shared-library') _

pipeline {
  agent any
  options {
    skipDefaultCheckout true
  }

  environment {
    credentialsId = 'gh-user'
    git_url = 'git@github.com:spiffdev/woocommerce-plugin.git'
    SERVICE = 'woocommerce-plugin'
  }

  stages {
    stage('Generate AWS vars') {
      steps {
        script {
          awsDetails = getAWSDetails()
          REGION = awsDetails['region']
        }
      }
    }

    stage('Check out Github repo') {
      steps {
        standardCheckOut([
          credentialsId: credentialsId,
          email: 'spiffdev@spiff.com.au',
          name: 'spiffdev',
          git_url: git_url
        ])
      }
    }

    stage('Run unit tests') {
      steps {
        sh 'docker run --rm -v $(pwd):/app phpunit/phpunit tests'
      }
    }

    stage('Output zip file') {
      steps {
        sh 'docker run --rm curlimages/curl https://assets.spiff.com.au/api.js > spiff-connect/public/js/api.js'
        sh 'docker run -u 1000 -v ${PWD}:/to_zip -w /to_zip --rm kramos/alpine-zip -r spiff-connect.zip spiff-connect'
        sh "aws --region ${REGION} s3 cp spiff-connect.zip s3://local.code.spiff.com.au/spiff-connect.zip"
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
