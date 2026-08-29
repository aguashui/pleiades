<?php
App::uses('ExceptionRenderer', 'Error');

/**
 * Custom ExceptionRenderer for displaying Exception information to the
 * bitfighter client. Whenever the User-Agent header of a request is exactly the
 * string 'Bitfighter', we set plain-text responses suitable for the game client.
 * For intentional HttpExceptions, we set the body to the message. For internal
 * 500 errors, we hide sensitive exception details unless debug mode is enabled.
 * Database connection errors are mapped to 503 Service Unavailable.
 */
class AppExceptionRenderer extends ExceptionRenderer {
	public function render() {
		if ($this->error instanceof MissingConnectionException || $this->error instanceof MissingDatasourceException) {
			$this->controller->response->statusCode(503);
			$this->controller->response->header('Retry-After', '60');

			if ($this->controller->request->header('User-Agent') === 'Bitfighter') {
				$this->controller->response->type('text');
				$this->controller->response->body('Database unavailable. Please try again later.');
				$this->controller->response->send();
				return;
			}
		}

		if ($this->controller->request->header('User-Agent') === 'Bitfighter') {
			$this->controller->response->type('text');

			if ($this->error instanceof HttpException) {
				$this->controller->response->statusCode($this->error->getCode());
				$this->controller->response->body($this->error->getMessage());
			} else {
				$this->controller->response->statusCode(500);
				$body = Configure::read('debug') > 0 ? $this->error->getMessage() : 'An Internal Error Has Occurred.';
				$this->controller->response->body($body);
			}

			$this->controller->response->send();
		} else {
			parent::render();
		}
	}
}