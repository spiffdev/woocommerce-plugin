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
    SVN_CREDS = credentials('wordpress-svn-user')
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

    stage('Merge back version bump') {
      steps {
        script {
          version = simpleSemanticVersion()
          env.VERSION = version
          writeFile file: 'VERSION', text: version
          createFeatureBranch([version: VERSION])
          mergeBackIn([version: VERSION, credentialsId: 'gh-user'])
        }
      }
    }

    stage('Write version') {
      steps {
        sh "awk 'NR==5{print \"Version: ${VERSION}\"}1' spiff-connect/spiff-connect.php > tmp && mv tmp spiff-connect/spiff-connect.php"
      }
    }

    stage('Output dev zip file') {
      steps {
        sh 'python replace-env-vars.py dev > tmp && mv tmp spiff-connect/spiff-connect.php'
        sh 'docker run --rm curlimages/curl https://assets.app.dev.spiff.com.au/api.js > spiff-connect/public/js/api.js'
        sh 'docker run -u 1000 -v ${PWD}:/to_zip -w /to_zip --rm kramos/alpine-zip -r spiff-connect-dev.zip spiff-connect'
        sh "aws --region ${REGION} s3 cp spiff-connect-dev.zip s3://local.code.spiff.com.au/spiff-connect-dev.zip"
      }
    }

    stage('Output prod zip file') {
      steps {
        sh 'python replace-env-vars.py prod > tmp && mv tmp spiff-connect/spiff-connect.php'
        sh 'docker run --rm curlimages/curl https://assets.spiff.com.au/api.js > spiff-connect/public/js/api.js'
        sh 'docker run -u 1000 -v ${PWD}:/to_zip -w /to_zip --rm kramos/alpine-zip -r spiff-connect.zip spiff-connect'
        sh "aws --region ${REGION} s3 cp spiff-connect.zip s3://local.code.spiff.com.au/spiff-connect.zip"
      }
    }

    stage('Commit to subversion') {
      steps {
        sh 'docker run --rm -v ${PWD}:${PWD} -w ${PWD} -u 1000 nbrun/svn-client:latest svn co https://plugins.svn.wordpress.org/spiff-3d-product-customizer svn-repo'
        sh 'cd svn-repo'
        sh 'cp ../spiff-connect/* trunk/'
        sh "docker run --rm -v ${PWD}:${PWD} -w ${PWD} -u 1000 nbrun/svn-client:latest svn --username ${SVN_CREDS_USR} --password ${SVN_CREDS_PSW} ci -m \"Version ${VERSION}\""
        sh "docker run --rm -v ${PWD}:${PWD} -w ${PWD} -u 1000 nbrun/svn-client:latest svn cp trunk tags/${VERSION}"
        sh "docker run --rm -v ${PWD}:${PWD} -w ${PWD} -u 1000 nbrun/svn-client:latest svn --username ${SVN_CREDS_USR} --password ${SVN_CREDS_PSW} ci -m \"Tag Version ${VERSION}\""
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
