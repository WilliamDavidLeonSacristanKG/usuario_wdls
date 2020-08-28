<?php

namespace Drupal\usuario_wdls\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Implements the Registro form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class Registro extends FormBase {

	protected $database;

	public function __construct(Connection $database) {
		$this->database = $database;
	}

	public static function create(ContainerInterface $container) {
		return new static(
			$container->get('database')
		);
	}

	public function buildForm(array $form, FormStateInterface $form_state) {

		$form['name'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Name'),
			'#description' => $this->t('The name must be at least 5 characters long.'),
			'#required' => TRUE,
		];

		$form['actions'] = [
			'#type' => 'button',
			'#value' => $this->t('Submit'),
			'#ajax' => [
				'callback' => '::submitFormUsuario',
			],
		];

		return $form;
	}

	public function getFormId() {
		return 'usuario_wdls_form_registro';
	}

	public function validateForm(array &$form, FormStateInterface $form_state) {
		$name = $form_state->getValue('name');
		if ($name == NULL || $name == '') {
			$form_state->setErrorByName('name', $this->t('The name must be complete.'));
		}
		if (strlen($name) < 5) {
			// Set an error for the form element with a key of "name".
			$form_state->setErrorByName('name', $this->t('The name must be at least 5 characters long.'));
		}
	}

	function submitFormUsuario(array &$form, FormStateInterface $form_state) {

		$element = $form['name']; 
		if ($form_state->getValue('name') == NULL || $form_state->getValue('name') == '') {
			$form_state->setErrorByName('name', $this->t('The name must be complete.'));
			$element['#markup'] = drupal_set_message(t('Necesario ingresar nombre valido '), 'warning', FALSE);
			return $element;
		}
		$this->database->insert('myusers')
		->fields([
			'nombre' => $form_state->getValue('name'),
		])
		->execute();
		$sth = $this->database->select('myusers', 'x')
			->fields('x', array('id'))
			->condition('x.nombre', $form_state->getValue('name'), '=');
		$data = $sth->execute();
		$id = $data->fetchAll(\PDO::FETCH_OBJ);
		// Iterate results
		foreach ($id as $row) {
   			$id_name = $row->id;
   		}
		// Try to get the selected text from the select element on our form.
		$selectedText = 'nothing selected';
		if ($selectedValue = $form_state->getValue('name')) {
			// Get the text of the selected option.
			$selectedText = $selectedValue;
		}
		// Create a new textfield element containing the selected text.
		// We're replacing the original textfield using an AJAX replace command which
		// expects HTML markup. So we need to render the textfield render array here.
		$elem = [
		    '#type' => 'textfield',
		    '#size' => '60',
		    '#disabled' => TRUE,
		    '#value' => "I am a new textfield: $selectedText!",
		    '#attributes' => [
		      'id' => ['edit-output'],
		    ],
		];
		$renderer = \Drupal::service('renderer');
		$renderedField = $renderer->render($elem);
		// Attach the javascript library for the dialog box command
		// in the same way you would attach your custom JS scripts.
		$dialogText['#attached']['library'][] = 'core/drupal.dialog.ajax';
		// Prepare the text for our dialogbox.
		$dialogText['#markup'] = "User ID: $id_name";
		// If we want to execute AJAX commands our callback needs to return
		// an AjaxResponse object. let's create it and add our commands.
		$response = new AjaxResponse();
		// Issue a command that replaces the element #edit-output
		// with the rendered markup of the field created above.
		$response->addCommand(new ReplaceCommand('#edit-output', $renderedField));
		// Show the dialog box.
		$response->addCommand(new OpenModalDialogCommand('Usuario Registrado '.$selectedText, $dialogText, ['width' => '300']));
		// Finally return the AjaxResponse object.
		return $response;
	}

	/**
	 * Submitting the form.
	 */
	public function submitForm(array &$form, FormStateInterface $form_state) {
	}
}