<?php
namespace Yannickl88\Component\CSS;

/**
 * Tokenizer based on the postcss/postcss JS implementation.
 *
 * @see https://github.com/postcss/postcss
 * @author Yannick de Lange <yannick.l.88@gmail.com>
 */
class Tokenizer
{
    const CHAR_SINGLEQUOTE  = "'";
    const CHAR_DOUBLEQUOTE  = "'";
    const CHAR_BACKSLASH    = '\\';
    const CHAR_SLASH        = '/';
    const CHAR_NEWLINE      = "\n";
    const CHAR_SPACE        = ' ';
    const CHAR_FEED         = "\f";
    const CHAR_TAB          = "\t";
    const CHAR_CR           = "\r";
    const CHAR_OPENBRACKET  = '(';
    const CHAR_CLOSEBRACKET = ')';
    const CHAR_OPENCURLY    = '{';
    const CHAR_CLOSECURLY   = '}';
    const CHAR_SEMICOLON    = ';';
    const CHAR_ASTERISK     = '*';
    const CHAR_COLON        = ':';
    const CHAR_AT           = '@';

    const REGEX_ATEND       = '/[ \n\t\r\{\(\)\'"\\\\;\/]/';
    const REGEX_WORDEND     = '/[ \n\t\r\(\)\{\}:;@!\'"\\\\]|\/(?=\*)/';
    const REGEX_BADBRACKET  = '/.[\\\\\/\("\'\n]/';

    /**
     * @return string[]
     */
    public static function getWhitespaces()
    {
        return [
            self::CHAR_SPACE,
            self::CHAR_NEWLINE,
            self::CHAR_TAB,
            self::CHAR_CR,
            self::CHAR_FEED
        ];
    }

    /**
     * @param string $string
     * @return Token[]
     */
    public function tokenize($string)
    {
        $tokens = [];
        $length = strlen($string);
        $offset = -1;
        $line   = 1;
        $pos    = 0;

        while ($pos < $length) {
            $code = $string[$pos];

            if ($code === self::CHAR_NEWLINE) {
                $offset = $pos;
                $line++;
            }

            switch ($code) {
                case self::CHAR_NEWLINE:
                case self::CHAR_SPACE:
                case self::CHAR_TAB:
                case self::CHAR_CR:
                case self::CHAR_FEED:
                    $next = $pos;
                    do {
                        $next++;
                        $code = $string[$next];

                        if ($code === self::CHAR_NEWLINE) {
                            $offset = $pos;
                            $line++;
                        }
                    } while (in_array($code, self::getWhitespaces()));

                    $tokens[] = new Token(Token::T_WHITESPACE, substr($string, $pos, $next - $pos), $line, $pos - $offset);
                    $pos      = $next - 1;
                    break;
                case self::CHAR_OPENCURLY:
                case self::CHAR_CLOSECURLY:
                case self::CHAR_COLON:
                case self::CHAR_SEMICOLON:
                case self::CHAR_CLOSEBRACKET:
                    $tokens[] = new Token(Token::T_CLOSEBRACKET, $code, $line, $pos - $offset);
                    break;
                case self::CHAR_OPENBRACKET:
                    $next    = strpos($string, ')', $pos + 1);
                    $content = substr($string, $pos, $next + 1 - $pos);

                    if ($next === false) {
                        $tokens[] = new Token(Token::T_OPENBRACKET, '(', $line, $pos - $offset);
                    } else {
                        $tokens[] = new Token(Token::T_BRACKETS, $content, $line, $pos - $offset, $line, $next - $offset);
                        $pos      = $next;
                    }
                    break;
                case self::CHAR_SINGLEQUOTE:
                case self::CHAR_DOUBLEQUOTE:
                    $quote = $code === self::CHAR_SINGLEQUOTE ? "'" : '"';
                    $next  = $pos;

                    do {
                        $escaped = false;
                        $next    = strpos($string, $quote, $next + 1);
                        if ($next === false) {
                            throw new \RuntimeException('unclosed');
                        }
                        $escape_pos = $next;
                        while ($string[$escape_pos] === self::CHAR_BACKSLASH) {
                            $escape_pos -= 1;
                            $escaped     = ! $escaped;
                        }
                    } while ($escaped);
                    $tokens[] = new Token(Token::T_STRING, substr($string, $pos, $next + 1 - $pos), $line, $pos - $offset, $line, $next - $offset);
                    $pos      = $next;
                    break;
                case self::CHAR_AT:
                    $matches = [];
                    if (preg_match(self::REGEX_ATEND, $string, $matches, PREG_OFFSET_CAPTURE, $pos + 1) === 0) {
                        $next = $length - 1;
                    } else {
                        $next = $matches[0][1] - 2 + strlen($matches[0][0]);
                    }
                    $tokens[] = new Token(Token::T_ATWORD, substr($string, $pos, $next + 1 - $pos), $line, $pos - $offset, $line, $next - $offset);
                    $pos      = $next;
                    break;
                case self::CHAR_BACKSLASH:
                    $next   = pos;
                    $escape = true;
                    while ($string[$next + 1] === self::CHAR_BACKSLASH) {
                        $next  += 1;
                        $escape = ! $escape;
                    }
                    $code = $string[$next + 1];
                    if ($escape && $code !== slash && ! in_array($code, self::getWhitespaces())) {
                        $next++;
                    }
                    $tokens[] = new Token(Token::T_WORD, substr($string, $pos, $next + 1 - $pos), $line, $pos - $offset, $line, $next - $offset);
                    $pos      = $next;
                    break;
                default:
                    if ($code === self::CHAR_SLASH && $string[$pos + 1] === self::CHAR_ASTERISK) {
                        $next = strpos($string, '*/', $pos + 2) + 1;
                        if (false === $next) {
                            throw new \RuntimeException('unclosed');
                        }

                        $content = substr($string, $pos, $next + 1);
                        $lines   = explode('\n', $content);
                        $last    = count($lines) - 1;

                        if ($last > 0) {
                            $nextLine   = $line + $last;
                            $nextOffset = $next - strlen($lines[$last]);
                        } else {
                            $nextLine   = $line;
                            $nextOffset = $offset;
                        }

                        $tokens[] = new Token(Token::T_COMMENT, $content, $line, $pos - $offset, $line, $next - $nextOffset);

                        $offset = $nextOffset;
                        $line   = $nextLine;
                        $pos    = $next;
                    } else {
                        $matches = [];
                        if (preg_match(self::REGEX_WORDEND, $string, $matches, PREG_OFFSET_CAPTURE, $pos + 1) === 0) {
                            $next = $length - 1;
                        } else {
                            $next = $matches[0][1] - 2 + strlen($matches[0][0]);
                        }

                        $tokens[] = new Token(Token::T_WORD, substr($string, $pos, $next + 1 - $pos), $line, $pos - $offset, $line, $next - $offset);
                        $pos      = $next;
                    }
                    break;
            }

            $pos++;
        }

        return $tokens;
    }
}
