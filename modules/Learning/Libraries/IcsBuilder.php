<?php

namespace Modules\Learning\Libraries;

/**
 * The calendar attachment that goes out with a booking confirmation.
 *
 * Worth doing properly rather than pasting the dates into the email body: a
 * class that is in somebody's calendar is a class they turn up to, and "I
 * forgot" is a refund request. It is also the cheapest possible answer to the
 * timezone problem — the file carries UTC instants and the recipient's own
 * calendar renders them wherever they are.
 *
 * Written by hand because RFC 5545 is small and the two things that actually
 * break iCalendar in the wild are both formatting rules a library would only
 * hide: lines longer than 75 octets, and unescaped commas and semicolons in the
 * text fields. Both are handled below.
 */
class IcsBuilder
{
    /**
     * @param list<array{
     *   uid:string, summary:string, description?:string, location?:string,
     *   start:string, end:string, timezone?:string, url?:string
     * }> $events  start/end are 'Y-m-d H:i:s' in the event's own timezone
     */
    public static function build(array $events, string $organiserName, string $organiserEmail): string
    {
        helper('norlanka');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//MyLearnPlus//Training//EN',
            'CALSCALE:GREGORIAN',
            // REQUEST would make the recipient's client offer to RSVP to us,
            // and nothing here is listening for a reply. PUBLISH says "here is
            // an event", which is what this is.
            'METHOD:PUBLISH',
        ];

        foreach ($events as $event) {
            $tz    = safe_timezone($event['timezone'] ?? null);
            $start = new \DateTimeImmutable($event['start'], $tz);
            $end   = new \DateTimeImmutable($event['end'], $tz);

            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:' . $event['uid'];
            // Everything in UTC with a trailing Z. A floating local time in an
            // ICS file is the classic way a learner in Dubai turns up at the
            // wrong hour.
            $lines[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z');
            $lines[] = 'DTSTART:' . $start->setTimezone(new \DateTimeZone('UTC'))->format('Ymd\THis\Z');
            $lines[] = 'DTEND:' . $end->setTimezone(new \DateTimeZone('UTC'))->format('Ymd\THis\Z');
            $lines[] = self::fold('SUMMARY:' . self::escape($event['summary']));

            if (! empty($event['description'])) {
                $lines[] = self::fold('DESCRIPTION:' . self::escape($event['description']));
            }
            if (! empty($event['location'])) {
                $lines[] = self::fold('LOCATION:' . self::escape($event['location']));
            }
            if (! empty($event['url'])) {
                $lines[] = self::fold('URL:' . self::escape($event['url']));
            }

            $lines[] = self::fold('ORGANIZER;CN=' . self::escape($organiserName) . ':mailto:' . $organiserEmail);
            $lines[] = 'STATUS:CONFIRMED';
            // A reminder the morning before and one an hour ahead. Two, because
            // a day's notice is what lets somebody rearrange, and an hour's is
            // what actually gets them to the desk.
            $lines[] = 'BEGIN:VALARM';
            $lines[] = 'TRIGGER:-P1D';
            $lines[] = 'ACTION:DISPLAY';
            $lines[] = self::fold('DESCRIPTION:' . self::escape($event['summary'] . ' — tomorrow'));
            $lines[] = 'END:VALARM';
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        // CRLF, not LF. Some clients are forgiving; Outlook is not.
        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Escape the characters RFC 5545 reserves in a text value.
     *
     * A comma in a course title — "Photoshop Level 1, Live Online" — silently
     * splits the value into two and produces an event with a truncated name.
     * Backslash first, or it escapes the escapes.
     */
    private static function escape(string $value): string
    {
        return str_replace(
            ['\\', "\r\n", "\n", "\r", ';', ','],
            ['\\\\', '\\n', '\\n', '\\n', '\;', '\\,'],
            $value
        );
    }

    /**
     * Fold a content line to 75 octets, continuing with a leading space.
     *
     * Octets, not characters: the limit is on bytes, and a Sinhala course title
     * is three bytes a glyph. Splitting mid-character produces a file that some
     * clients reject outright, so the fold walks the string by character and
     * measures in bytes.
     */
    private static function fold(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }

        $out     = '';
        $current = '';
        foreach (preg_split('//u', $line, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $char) {
            // 74 leaves room for the leading space on the continuation line.
            if (strlen($current) + strlen($char) > 74) {
                $out    .= $current . "\r\n ";
                $current = '';
            }
            $current .= $char;
        }

        return $out . $current;
    }
}
