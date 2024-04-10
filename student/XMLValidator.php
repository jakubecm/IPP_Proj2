<?php

namespace IPP\Student;

use IPP\Core\Exception\InternalErrorException;
use IPP\Student\Exceptions\SourceStructureException;

class XMLValidator
{
    public static function validateXMLStructure(\DOMDocument $xmlFile, Interpreter $interpret): void
    {
        $xpath = new \DOMXPath($xmlFile);
        $instructions = $xpath->query('//instruction');
        $orderValues = [];

        foreach ($instructions as $instruction) {

            if (!$instruction instanceof \DOMElement) {

                $interpret->writeError("Invalid XML structure.");
                exit(31);
            }

            $order = $instruction->getAttribute('order');

            if (isset($orderValues[$order])) {

                throw new SourceStructureException("Duplicate order '$order' found.");
            }
            if (intval($order) < 1) {

                throw new SourceStructureException("Negative order '$order' found.");
            }
            $orderValues[$order] = true;

            // Validate the sequence of arguments
            self::validateArgumentSequence($instruction);
        }

        // Validate allowed parts of the XML tree
        $invalidNodes = $xpath->query('//*[not(self::program or self::instruction or self::arg1 or self::arg2 or self::arg3)]');
        if ($invalidNodes->length > 0) {

            throw new SourceStructureException("Invalid XML structure.");
        }
    }

    private static function validateArgumentSequence(\DOMElement $instruction): void
    {
        $maxArgs = 3; // Adjust based on the maximum number of arguments you expect
        $lastArgFound = 0;

        for ($i = 1; $i <= $maxArgs; $i++) {
            $arg = $instruction->getElementsByTagName("arg$i")->item(0);
            if ($arg !== null) {
                if ($i > $lastArgFound + 1) {
                    // If there's a gap in the sequence, throw an error
                    throw new SourceStructureException("Invalid XML structure - arg$i exists without arg" . ($i - 1));
                }
                $lastArgFound = $i;
            }
        }
    }
}
