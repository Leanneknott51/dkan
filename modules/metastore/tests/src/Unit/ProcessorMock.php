<?php
namespace Drupal\Tests\metastore\Unit;


use FileFetcher\Processor\ProcessorInterface;
use Procrastinator\Result;

class ProcessorMock implements ProcessorInterface {

  public function isServerCompatible(array $state): bool {
    return TRUE;
  }

  public function setupState(array $state): array {
    return $state;
  }

  public function copy(
    array $state,
    Result $result,
    int $timeLimit = PHP_INT_MAX
  ): array {
    $result->setStatus(Result::DONE);
    return [
      'state' => $state,
      'result' => $result
    ];
  }

  public function isTimeLimitIncompatible(): bool {
    return TRUE;
  }

}
