<?php

final class ArcanistElixirDogmaLinter extends ArcanistExternalLinter {

  public function getInfoName() {
    return 'ElixirDogma';
  }

  public function getInfoURI() {
    return 'https://github.com/lpil/dogma';
  }

  public function getInfoDescription() {
    return pht('A code style linter for Elixir, powered by shame.');
  }

  public function getLinterName() {
    return 'ELIXIRDOGMA';
  }

  public function getLinterConfigurationName() {
    return 'elixirdogma';
  }

  public function getDefaultBinary() {
    return 'mix';
  }

  protected function getMandatoryFlags() {
    return array('dogma', '--noerrors', '--format=flycheck');
  }

  public function getInstallInstructions() {
    return pht(
      'Install dogma by adding it as a dependency to your deps and
      executing mix deps.get');
  }

  public function shouldExpectCommandErrors() {
    return false;
  }

  protected function canCustomizeLintSeverities() {
    return false;
  }

  protected function parseLinterOutput($path, $err, $stdout, $stderr) {
    $lines = phutil_split_lines($stdout, false);

    $messages = array();
    foreach ($lines as $line) {
      $matches = explode(':', $line, 5);

      if (count($matches) === 5) {
        $message = new ArcanistLintMessage();
        $message->setPath($path);
        $message->setLine($matches[1]);
        $message->setChar($matches[2]);
        $message->setCode($this->getLinterName());
        $message->setName($this->getLinterName());
        $message->setDescription(ucfirst(trim($matches[4])));
        $message->setSeverity($this->getSeverity(ucfirst(trim($matches[3]))));

        $messages[] = $message;
      }
    }

    return $messages;
  }

  private function getSeverity($identifier) {
    switch ($identifier) {
    case 'W':
      return ArcanistLintSeverity::SEVERITY_WARNING;
    case 'E':
      return ArcanistLintSeverity::SEVERITY_ERROR;
    }
    return ArcanistLintSeverity::SEVERITY_ADVICE;
  }
}
