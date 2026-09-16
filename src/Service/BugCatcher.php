<?php
/**
 * Created by PhpStorm.
 * User: Jozef Môstka
 * Date: 12. 6. 2024
 * Time: 15:29
 */
namespace BugCatcher\Reporter\Service;

use BugCatcher\Reporter\Event\RecordWriteEvent;
use BugCatcher\Reporter\UrlCatcher\UriCatcherInterface;
use BugCatcher\Reporter\Writer\CollectCodeFrame;
use BugCatcher\Reporter\Writer\WriterInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Throwable;

class BugCatcher implements BugCatcherInterface {

	public function __construct(
		private readonly WriterInterface     $writer,
		private readonly UriCatcherInterface $uriCatcher,
        private readonly EventDispatcherInterface $eventDispatcher,
		private readonly string              $project,
		private readonly string              $minLevel
	) {}

	public function log(array $data): void {
		if (!array_key_exists("projectCode", $data)) {
			$data["projectCode"] = $this->project;
		}
        $event = $this->eventDispatcher->dispatch(new RecordWriteEvent($data));
        $this->writer->write($event->getData());
	}

	public function logRecord(string $message, int $level, ?string $requestUri = null, array $additional = []): void {
        $data = $additional + [
                "api_uri" => "/api/record_logs",
                "message" => $this->normalizeMessage($message),
                "level" => $level,
                "projectCode" => $this->project,
                "requestUri" => $requestUri ?? $this->uriCatcher->getUri(),
            ];
        $event = $this->eventDispatcher->dispatch(new RecordWriteEvent($data));
        $this->writer->write($event->getData());
	}

	public function logException(Throwable $throwable, int $level = 500, ?string $requestUri = null, ?string $meCode = null): void {

		$data = [
			"api_uri" => "/api/record_log_traces",
			"message" => $this->normalizeMessage($throwable->getMessage(), $throwable),
			"level"       => $level,
			"projectCode" => $this->project,
			"requestUri"  => $requestUri??$this->uriCatcher->getUri(),
		];
		if ($meCode) {
			$data["code"] = $meCode;
		}
        $event = $this->eventDispatcher->dispatch(new RecordWriteEvent($data, $throwable));
        $this->writer->write($event->getData());
	}

	/**
	 * A throwable with an empty message is still worth reporting - often it is the one you most
	 * need to see. The API rejects a blank `message` though, so the whole record would be lost
	 * and the write would throw back into the application. Fall back to the throwable class,
	 * which always identifies the error.
	 */
	private function normalizeMessage(string $message, ?Throwable $throwable = null): string {
		if (trim($message) === '') {
			return $throwable ? $throwable::class : 'Empty log message';
		}

		return substr($message, 0, 750);
	}
}