<?php declare(strict_types = 1);

namespace Trejjam\Sentry\Logger;

use Sentry\Event;
use Sentry\Severity;
use Sentry\State\HubInterface;
use Throwable;
use Tracy\ILogger;

final readonly class SentryLogger implements ILogger
{

	public function __construct(
		private HubInterface $hub,
		private bool $disabled
	)
	{
	}

	public function log(mixed $value, string $level = ILogger::INFO): void
	{
		if ($this->disabled) {
			return;
		}

		$severity = $this->getSeverityFromPriority($level);

		if ($severity === null) {
			return;
		}

		$event = Event::createEvent();

		$event->setLevel($severity);

		if ($value instanceof Throwable) {
			$this->hub->captureException($value);

			return;
		}

		$event->setMessage((string) $value);

		$this->hub->captureEvent($event);
	}

	private function getSeverityFromPriority(string $level): ?Severity
	{
		return match ($level) {
			ILogger::WARNING => Severity::warning(),
			ILogger::ERROR => Severity::error(),
			ILogger::EXCEPTION, ILogger::CRITICAL => Severity::fatal(),
			default => null,
		};
	}

}
