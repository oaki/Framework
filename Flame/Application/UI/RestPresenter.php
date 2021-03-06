<?php
/**
 * RestPresenter.php
 *
 * @author  Jiří Šifalda <sifalda.jiri@gmail.com>
 * @date    05.02.13
 */

namespace Flame\Application\UI;

use Flame\Utils\Strings;
use Nette\Application\ForbiddenRequestException;
use Nette\Diagnostics\Debugger;
use Nette\InvalidStateException;
use Nette\Reflection\Method;
use Nette;

abstract class RestPresenter extends Presenter
{

	const STATUS_SUCCESS = 'success',
		STATUS_ERROR = 'error';

	/**
	 * @return mixed
	 */
	public function getPostData()
	{
		return $this->getHttpRequest()->getPost();
	}

	/**
	 * @param $element
	 */
	public function checkRequirements($element)
	{

		try {
			parent::checkRequirements($element);
		} catch (ForbiddenRequestException $ex) {
			$this->returnException($ex);
		}

		try {
			$this->checkMethodRequest($element);
		} catch (InvalidStateException $ex) {
			$this->returnException($ex);
		}
	}

	/**
	 * @return Nette\Templating\IFileTemplate|Nette\Templating\ITemplate|void
	 */
	public function renderTemplate()
	{
		$template = $this->getTemplate();
		if (!$template) {
			return;
		}

		if ($template instanceof Nette\Templating\IFileTemplate && !$template->getFile()) { // content template
			$files = $this->formatTemplateFiles();
			foreach ($files as $file) {
				if (is_file($file)) {
					$template->setFile($file);
					break;
				}
			}

			if (!$template->getFile()) {
				$file = preg_replace('#^.*([/\\\\].{1,70})\z#U', "\xE2\x80\xA6\$1", reset($files));
				$file = strtr($file, '/', DIRECTORY_SEPARATOR);
				$this->error("Page not found. Missing template '$file'.");
			}
		}

		return $template;
	}

	/**
	 * @param \Exception $ex
	 * @param int        $code
	 */
	protected function returnException(\Exception $ex, $code = null)
	{
		Debugger::log($ex);

		if ($code === null && $ex->getCode()) {
			$code = $ex->getCode();
		} elseif ($code === null) {
			$code = 500;
		}

		$this->payload->status = self::STATUS_ERROR;
		$this->payload->message = $ex->getMessage();
		$this->payload->code = $code;

		$this->getHttpResponse()->setCode($code);

		$this->sendJson($this->getPayload());
	}


	/**
	 * @param array $data
	 * @param int   $code
	 */
	protected function returnResponse(array $data = array(), $code = 200)
	{
		$this->payload->data = $data;
		$this->payload->code = $code;
		$this->payload->status = self::STATUS_SUCCESS;

		$this->getHttpResponse()->setCode($code);

		$this->sendJson($this->getPayload());
	}

	/**
	 * @param $element
	 * @throws \Nette\InvalidStateException
	 */
	protected function checkMethodRequest($element)
	{
		if ($anot = $element->getAnnotation('method')) {
			$reguest = $this->getHttpRequest();
			if (Strings::lower($anot) !== Strings::lower($reguest->getMethod()))
				throw new InvalidStateException('Bad method for this request. ' . $element->getDeclaringClass() . '::' . $element->getName());
		} else {
			if ($element instanceof Method)
				throw new InvalidStateException('@method annotation is not set for method ' . $element->getDeclaringClass() . '::' . $element->getName());
		}
	}
}
