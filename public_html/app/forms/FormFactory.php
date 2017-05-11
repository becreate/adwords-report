<?php

namespace App\Forms;

use Nette;
use Nette\Application\UI\Form;
use Tomaj\Form\Renderer\BootstrapRenderer;

class FormFactory
{
	use Nette\SmartObject;

	/**
	 * @return Form
	 */
	public function create()
	{
		$form = new Form;
                $form->setRenderer(new BootstrapRenderer);
		return $form;
	}

}
