pipeline {
  agent any

  environment {
    DEPLOY_DIR = "E:\xampp\htdocs\muj"   // change if your XAMPP is elsewhere
    REPO_URL = 'https://github.com/Vighneshnikam/muj-project-allocation.git'
    BRANCH = 'main' // or master
  }

  stages {
    stage('Checkout') {
      steps {
        // For a public repo this works. If private, configure credentials and use credentialsId.
        git branch: "${BRANCH}", url: "${REPO_URL}"
      }
    }

    stage('Prepare deploy dir') {
      steps {
        // Delete old files (be careful) then recreate folder
        bat """
        if exist "${DEPLOY_DIR}" (
          rmdir /S /Q "${DEPLOY_DIR}"
        )
        mkdir "${DEPLOY_DIR}"
        """
      }
    }

    stage('Copy files to webserver') {
      steps {
        // Use robocopy for robust copy (excludes .git)
        bat """
        robocopy "%WORKSPACE%" "${DEPLOY_DIR}" /MIR /XD .git
        """
      }
    }

    stage('Notify') {
      steps {
        echo "Deployed to ${DEPLOY_DIR}"
      }
    }
  }

  post {
    success {
      echo "Pipeline finished successfully."
    }
    failure {
      echo "Pipeline failed."
    }
  }
}
