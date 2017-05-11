<?php

namespace App\Presenters;

use Nette;
use App\Forms;


class SignPresenter extends BasePresenter
{
	/** @var Forms\SignInFormFactory @inject */
	public $signInFactory;

	/** @var Forms\SignUpFormFactory @inject */
	public $signUpFactory;

	public function actionIn()
	{
		$this['breadcrumb']->addLink('Přihlášení');
	}

	public function actionUp()
	{
		$this['breadcrumb']->addLink('Registrace');
	}

	public function actionOut()
	{
		$this->getUser()->logout();
		$this->redirect('Homepage:');
	}

	/**
	 * Sign-in form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSignInForm()
	{
		return $this->signInFactory->create(function () {
			$this->redirect('Account:');
		});
	}


	/**
	 * Sign-up form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSignUpForm()
	{
		return $this->signUpFactory->create(function () {
			$this->redirect('Sign:in');
		});
	}

}
