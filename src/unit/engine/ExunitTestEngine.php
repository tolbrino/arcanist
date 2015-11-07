<?php

/**
 * Very basic 'mix test' unit test engine wrapper.
 *
 * It requires the use of the junit_formatter to produce the xml report.
 * Refer to https://hex.pm/packages/junit_formatter
 *
 * Cover reporting is not supported yet.
 *
 * Note: When using mix configurations you likely want to execute the
 * unit tests with the correct environment, e.g. `MIX_ENV=test arc unit`
 */
final class ExunitTestEngine extends ArcanistUnitTestEngine {

  private $projectRoot;

  public function run() {
    $working_copy = $this->getWorkingCopy();
    $this->projectRoot = $working_copy->getProjectRoot();

    // this is the standard report location when using mix test
    $junit_file = $this->projectRoot . "/_build/test/test-junit-report.xml";
    $cover_tmp = new TempFile();

    $future = $this->buildTestFuture($junit_file, $cover_tmp);
    list($err, $stdout, $stderr) = $future->resolve();

    if (!Filesystem::pathExists($junit_file)) {
      throw new CommandException(
        pht('Command failed with error #%s!', $err),
        $future->getCommand(),
        $err,
        $stdout,
        $stderr);
    }

    return $this->parseTestResults($junit_file, $cover_tmp);
  }

  public function buildTestFuture($junit_tmp, $cover_tmp) {
    $paths = $this->getPaths();

    $cmd_line = csprintf('mix test --junit');

    return new ExecFuture('%C', $cmd_line);
  }

  public function parseTestResults($junit_tmp, $cover_tmp) {
    $parser = new ArcanistXUnitTestResultParser();
    $results = $parser->parseTestResults(
      Filesystem::readFile($junit_tmp));

    return $results;
  }

}
