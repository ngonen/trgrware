<?php

class SiteController extends Controller
{
	/**
	 * Declares class-based actions.
	 */
	public function actions()
	{
		return array(
			// captcha action renders the CAPTCHA image displayed on the contact page
//			'captcha'=>array(
//				'class' => 'CCaptchaAction',
//				'backColor' => 0xFFFFFF,
//			),
			// page action renders "static" pages stored under 'protected/views/site/pages'
			// They can be accessed via: index.php?r=site/page&view=FileName
			'page' => array(
				'class' => 'CViewAction',
			),
		);
	}

	/**
	 * This is the default 'index' action that is invoked
	 * when an action is not explicitly requested by users.
	 */
	public function actionIndex()
	{
        $this->render('index');
	}

	/**
	 * This is the action to handle external exceptions.
	 */
	public function actionError()
	{
		if ($error=Yii::app()->errorHandler->error)
		{
			if (Yii::app()->request->isAjaxRequest) {
                echo $error['message'];
            } else {
                $this->render('error', $error);
            }
		}
	}

	/**
	 * Displays the contact page
	 */
	public function actionContact()
	{
		$model = new ContactForm;

		if (isset($_POST['ContactForm']))
		{
			$model->attributes = $_POST['ContactForm'];

			if ($model->validate())
			{
//				$name = '=?UTF-8?B?' . base64_encode($model->name) . '?=';
//				$subject = '=?UTF-8?B?' . Yii::app()->params['contactUs']['subject'] . '?=';
//				$headers = "From: $name <{$model->email}>\r\n".
//					"Reply-To: {$model->email}\r\n".
//					"MIME-Version: 1.0\r\n".
//					"Content-Type: text/plain; charset=UTF-8";
//
//				$isSent = mail(Yii::app()->params['contactUs']['adminEmail'], $subject, $model->body, $headers);
//
//                Yii::app()->user->setFlash('contact', $isSent
//                    ? 'Thank you for contacting us. We will respond to you as soon as possible.'
//                    : 'Error occurred when trying to send you message.');

                require_once 'google/appengine/api/mail/Message.php';

                try {
                    $message = new \google\appengine\api\mail\Message();
                    $message->setSender(Yii::app()->params['contactUs']['adminEmail']);
                    $message->addTo(Yii::app()->params['contactUs']['adminEmail']);
                    $message->setReplyTo($model->email);
                    $message->setSubject(Yii::app()->params['contactUs']['subject']);
                    $message->setTextBody($model->body);
                    $message->send();
                } catch (InvalidArgumentException $exc) {
                    Yii::app()->user->setFlash('contact', 'Your message was not sent.');

                    $this->refresh();
                }

				Yii::app()->user->setFlash('contact', 'Thank you for contacting us. We will respond to you as soon as possible.');

				$this->refresh();
			}
		}

		$this->render('contact', array('model' => $model));
	}

	/**
	 * Displays the login page
	 */
	public function actionLogin()
	{
        $model = new LoginForm;

		// if it is ajax validation request
		if (isset($_POST['ajax']) && $_POST['ajax']==='login-form')
		{
			echo CActiveForm::validate($model);

			Yii::app()->end();
		}

		// collect user input data
		if (isset($_POST['LoginForm']))
		{
			$model->attributes = $_POST['LoginForm'];
			// validate user input and redirect to the previous page if valid
			if ($model->validate() && $model->login()) {
                $this->redirect(Yii::app()->user->returnUrl);
            }
		}

        require_once 'google/appengine/api/users/UserService.php';

        $googleLoginUrl = \google\appengine\api\users\UserService::createLoginURL('/site/googleLogin');

		// display the login form
		$this->render('login', array('model' => $model, 'googleLoginUrl' => $googleLoginUrl));
	}

	/**
	 * Logs out the current user and redirect to homepage.
	 */
	public function actionLogout()
	{
		Yii::app()->user->logout();

		$this->redirect(Yii::app()->homeUrl);
	}

    public function actionGoogleLogin()
    {
        $identity = new GUserIdentity('test','test');
        $identity->authenticate();

        if($identity->errorCode === GUserIdentity::ERROR_NONE) {
            Yii::app()->user->login($identity, $duration = 0);

            $this->redirect('/');
        } else {
            $this->redirect('/site/login');
        }
    }

    // TODO: delete
    public function actionTest()
    {
        echo '<pre>';

        var_dump(Yii::app()->basePath . '/../assets');
        var_dump(is_file(Yii::app()->basePath . "/../assets/file.txt"));

        echo "is_file('gs://assets/file.txt');";
        var_dump(is_file('gs://yii-assets/dir/file.txt'));

        echo "is_dir('gs://yii-assets/dir');";
        var_dump(is_dir('gs://yii-assets/dir'));

        echo "is_file('gs://yii-assets/nodir/nofile.txt');";
        var_dump(is_file('gs://yii-assets/nodir/nofile.txt'));

        echo "is_dir('gs://yii-assets/nodir');";
        var_dump(is_dir('gs://yii-assets/nodir'));

        echo "filemtime('gs://yii-assets/dir/file.txt');";
        var_dump(filemtime('gs://yii-assets/dir/file.txt'));
        echo '</pre>';
    }

    // TODO: delete
    public function actionQueueAdd() {
        require_once 'google/appengine/api/taskqueue/PushTask.php';
        require_once 'google/appengine/api/taskqueue/PushQueue.php';

        $task1 = new \google\appengine\api\taskqueue\PushTask("/site/QueueTask",array(),['method'=> 'GET']);
        $queue = new \google\appengine\api\taskqueue\PushQueue("foo");
        $queue->addTasks([$task1]);

        $a = 2;
    }

    // TODO: delete
    public function actionQueueTask() {
        $a = 2;

        echo 'Queue Task was executed.';
    }

    // TODO: delete
    public function actionTestCron() {
        syslog(LOG_INFO, 'TestCron function was executed.');
    }

    // TODO: delete
    public function actionFile() {
        $options = [ "gs" => [ "Content-Type" => "text/plain" ]];
        $ctx = stream_context_create($options);
        file_put_contents("gs://gae-yii.appspot.com/myFile.txt", "Hello", 0, $ctx);

        $fp = fopen("gs://gae-yii.appspot.com/file.txt", 'w');
        fwrite($fp, 'Hello ');
        fwrite($fp, 'world');
        fwrite($fp, '!');
        fclose($fp);

//        return file_get_contents("gs://gae-yii.appspot.com/myFile.txt");
    }
}