<?php

/**
 * Lists open revisions in Differential.
 */
final class ArcanistListWorkflow extends ArcanistWorkflow {

  public function getWorkflowName() {
    return 'list';
  }

  public function getCommandSynopses() {
    return phutil_console_format(<<<EOTEXT
      **list**
EOTEXT
      );
  }

  public function getCommandHelp() {
    return phutil_console_format(<<<EOTEXT
          Supports: git, svn, hg
          List your open Differential revisions.
EOTEXT
      );
  }

  public function getArguments() {
    return array(
      'as-reviewer' => array(
        'help' => pht(
          'List revision where you are assigned as reviewer'),
      ));
  }

  private function shouldShowAsReviewer() {
    return $this->getArgument('as-reviewer', false);
  }

  public function requiresConduit() {
    return true;
  }

  public function requiresRepositoryAPI() {
    return true;
  }

  public function requiresAuthentication() {
    return true;
  }

  public function run() {
    static $color_map = array(
      'Closed'          => 'cyan',
      'Needs Review'    => 'magenta',
      'Needs Revision'  => 'red',
      'Changes Planned' => 'red',
      'Accepted'        => 'green',
      'No Revision'     => 'blue',
      'Abandoned'       => 'default',
    );

    $query_opts = array('status'  => 'status-open');
    $phids = array($this->getUserPHID());
    if ($this->shouldShowAsReviewer()) {
        $query_opts = array_merge($query_opts, array('reviewers' => $phids));
    } else {
        $query_opts = array_merge($query_opts, array('authors' => $phids));
    }
    $revisions = $this->getConduit()->callMethodSynchronous(
      'differential.query', $query_opts);

    if (!$revisions) {
      echo pht('You have no open Differential revisions.')."\n";
      return 0;
    }

    $repository_api = $this->getRepositoryAPI();

    $info = array();
    foreach ($revisions as $key => $revision) {
      $revision_path = Filesystem::resolvePath($revision['sourcePath']);
      $current_path  = Filesystem::resolvePath($repository_api->getPath());
      if ($revision_path == $current_path) {
        $info[$key]['exists'] = 1;
      } else {
        $info[$key]['exists'] = 0;
      }
      $info[$key]['sort'] = sprintf(
        '%d%04d%08d',
        $info[$key]['exists'],
        $revision['status'],
        $revision['id']);
      $info[$key]['statusName'] = $revision['statusName'];
      $info[$key]['color'] = idx(
        $color_map, $revision['statusName'], 'default');
    }

    $table = id(new PhutilConsoleTable())
      ->setShowHeader(false)
      ->addColumn('exists', array('title' => ''))
      ->addColumn('status', array('title' => pht('Status')))
      ->addColumn('title',  array('title' => pht('Title')));

    $info = isort($info, 'sort');
    foreach ($info as $key => $spec) {
      $revision = $revisions[$key];

      $table->addRow(array(
        'exists' => $spec['exists'] ? tsprintf('**%s**', '*') : '',
        'status' => tsprintf(
          "<fg:{$spec['color']}>%s</fg>",
          $spec['statusName']),
        'title'  => tsprintf(
          '**D%d:** %s',
          $revision['id'],
          $revision['title']),
      ));
    }

    $table->draw();
    return 0;
  }

}
