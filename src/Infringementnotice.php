<?php

namespace AbuseIO\Parsers;

use AbuseIO\Models\Incident;

/**
 * Class Infringementnotice
 * @package AbuseIO\Parsers
 */
class Infringementnotice extends Parser
{
    /**
     * Create a new Infringementnotice instance
     *
     * @param \PhpMimeMailParser\Parser $parsedMail phpMimeParser object
     * @param array $arfMail array with ARF detected results
     */
    public function __construct($parsedMail, $arfMail)
    {
        // Call the parent constructor to initialize some basics
        parent::__construct($parsedMail, $arfMail, $this);
    }

    /**
     * Parse attachments
     * @return array    Returns array with failed or success data
     *                  (See parser-common/src/Parser.php) for more info.
     */
    public function parse()
    {
        // XML is placed in the body
        if (preg_match(
		"/(<\?xml[^>]*>.*?<\/Infringement>)/is",
            $this->parsedMail->getMessageBody(),
            $regs
        )) {
            $report = $regs[0];
            $this->feedName = 'default';

            if (!empty($report) && $report = simplexml_load_string($report)) {

                if ($this->isKnownFeed() && $this->isEnabledFeed()) {

                    /*
                     * Work around the crappy timestamp used by IP-echelon, i.e.: 2015-05-06T05-00-00UTC
                     * We loose some timezone information, but hey it's close enough ;)
                     */
                    if (preg_match(
                        '/^([0-9-]+)T([0-9]{2})-([0-9]{2})-([0-9]{2})/',
                        $report->Source->TimeStamp,
                        $regs
                    )) {
                        $timestamp = strtotime($regs[1].' '.$regs[2].':'.$regs[3].':'.$regs[4]);
                        // Fall back to now if we can't parse the timestamp
                    } else {
                        $timestamp = time();
                    }

                    /*
                     * The XML contains so many crap you cant even think about filters here so we grab
                     * the fields ourselves.
                     */
                    $infoBlob = [
			'case id'	=> (string)$report->Case->ID,
                        'complainant'   => (string)$report->Complainant->Entity,
			'source email'	=> (string)$report->Complainant->Email,
			'filename'	=> (string)$report->Content->Item->FileName,
                        'type'          => (string)$report->Source->Type,
                        'timestamp'     => (string)$report->Source->TimeStamp,
                        'port'          => (string)$report->Source->Port,
                    ];

                    $incident = new Incident();
                    $incident->source      = config("{$this->configBase}.parser.name");
                    $incident->source_id   = false;
                    $incident->ip          = (string)$report->Source->IP_Address;
                    $incident->domain      = false;
                    $incident->class       = config("{$this->configBase}.feeds.{$this->feedName}.class");
                    $incident->type        = config("{$this->configBase}.feeds.{$this->feedName}.type");
                    $incident->timestamp   = $timestamp;
                    $incident->information = json_encode($infoBlob);

                    $this->incidents[] = $incident;

                }
            } else { // We cannot pass XML validation or load object
                $this->warningCount++;
            }
        } else { // We cannot collect XML
            $this->warningCount++;
        }

        return $this->success();
    }
}
