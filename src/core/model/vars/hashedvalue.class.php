<?php namespace Spaark\Core\Model\Vars;
/**
 * Spaark
 *
 * Copyright (C) 2012 Emily Shepherd
 * emily@emilyshepherd.me
 */

use \Spaark\Core\Config\Config;

/**
 * Represents a value that is stored as a hash
 */
class HashedValue extends String
{
    /**
     * The appliction salt
     *
     * This should be changed from the default before release
     */
    protected $appSalt     = '1Ltkp0+30sdg!f4L={p';

    /**
     * The strength required by the application
     */
    protected $appStrength = 7;

    /**
     * The strength of this hash
     */
    protected $strength    = 7;

    /**
     * Calculates the hash of $this->value.
     *
     * If a hash is provided, it will take the salt from it, otherwise
     * it will generate a random one
     *
     * @param string $hash If provided, use the salt in this
     */
    public function hash($hash = NULL)
    {
        if ($hash)
        {
            if (substr($hash, 0, 4) != '$2a$')
            {
                throw new Exception('Unsupported');
            }

            $this->strength = (int)substr($hash, 4, 2);

            $salt = substr($hash, 7, 21);
        }
        else
        {
            $salt = substr(sha1(uniqid()), 0, 21);

            $this->strength = $this->appStrength;
        }

        //The hash is generated by sowing the value and application salt
        //together, and then taking the blowfish hash of it with salt
        return crypt
        (
            $this->scramble
            (
                $this->value . $this->appSalt,
                ((strlen($this->value) << 1) % 11) + 5
            ),
            '$2a$0' . $this->strength . '$' . $salt . '$'
        );
    }

    /**
     * Scrambles the given string using the Mersenne Twister algorithm
     * with the given seed
     *
     * If a length is set, and the string length is less than it, the
     * remaining spaces will be filed with the next values of the
     * Mersenne Twister algorithm.
     *
     * @param string $string The string to scramble
     * @param int $seed The seed
     * @param int $length The required length
     * @return string The scrambled string, with padding if required
     */
    private function scramble($string, $seed, $length = NULL)
    {
        $outArr    = array( );

        $stringLen = strlen($string);
        $len       = $length ?: $stringLen;

        mt_srand($seed);

        //Loop through the input string and place each character in the
        //next free space, chosen by the Mersenne Twister algorithm
        for ($i = 0; $i < $stringLen; $i++)
        {
            do
            {
                $pos = mt_rand(0, $len - 1);
            }
            while (isset($outArr[$pos]));

            $outArr[$pos] = $string[$i];
        }

        //If a desired length is specified, we need to fill the spaces
        //with "random" characters. (This need to be reproducable so the
        //hash is always the same.)
        if ($length)
        {
            $chars   = array_merge(range('0', '9'), range('a', 'z'));
            $clen    = count($chars) - 1;

            for ($i = 0; $i < $length; $i++)
            {
                if (!isset($outArr[$i]))
                {
                    $outArr[$i] = $chars[mt_rand(0, $clen)];
                }
            }
        }

        ksort($outArr);

        return implode('', $outArr);
    }

    /**
     * Harvest the specified number of characters from the given
     * scrambled string
     *
     * @param string $string The scambled string
     * @param int $length The number of characters to get
     * @param int $seed The seed for the Mersenne Twister algorithm
     * @return string The unscambled content
     * @depreciated
     */
    private function unscramble($string, $length, $seed)
    {
        $out  = '';
        $used = array( );

        mt_srand($seed);

        for ($i = 0; $i < $length; $i++)
        {
            do
            {
                $pos = mt_rand(0, strlen($string) - 1);
            }
            while (isset($used[$pos]));

            $used[$pos] = true;
            $out       .= $string[$pos];
        }

        return $out;
    }

    /**
     * Checks if the given hash is for this HashedValue
     *
     * @param string $hash The hash to check
     * @return boolean If the hash is for this HashValue
     */
    public function checkHash($hash)
    {
        return $hash == $this->hash($hash);
    }
}

