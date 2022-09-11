<?= !empty($videostream) && $videostream instanceof Mvc\Helpers\VideoStream ? $videostream->start() : ($error ?? "Error") ?>
