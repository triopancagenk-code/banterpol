<?php

class RouterosAPI
{
    public $debug     = false;
    public $connected = false;
    public $port      = 8728;
    public $timeout   = 2;
    public $socket;
    public $error_no;
    public $error_str;

    public function connect($ip, $login, $password, $port = 8728)
    {
        $this->connected = false;
        $this->port = $port;
        $this->socket = @fsockopen($ip, $this->port, $this->error_no, $this->error_str, $this->timeout);

        if (!$this->socket) {
            return false;
        }

        stream_set_timeout($this->socket, $this->timeout);

        // 1. Kirim autentikasi langsung
        $this->write('/login', false);
        $this->write('=name=' . $login, false);
        $this->write('=password=' . $password, true);

        $STATUS = $this->read(false);

        if (isset($STATUS[0]) && $STATUS[0] === '!done') {
            if (!isset($STATUS[1])) {
                $this->connected = true;
                return true;
            }

            // Challenge-response fallback untuk versi lawas
            if (preg_match('/=ret=(.*)/', $STATUS[1], $matches)) {
                $challenge = $matches[1];
                $this->write('/login', false);
                $this->write('=name=' . $login, false);
                $this->write('=response=00' . md5(chr(0) . $password . pack('H*', $challenge)), true);
                $STATUS2 = $this->read(false);
                if (isset($STATUS2[0]) && $STATUS2[0] === '!done') {
                    $this->connected = true;
                    return true;
                }
            }
        }

        $this->disconnect();
        return false;
    }

    public function disconnect()
    {
        if (is_resource($this->socket)) {
            @fclose($this->socket);
        }
        $this->connected = false;
    }

    public function comm($com, $arr = [])
    {
        $count = count($arr);
        $this->write($com, !$count);
        $i = 0;
        foreach ($arr as $k => $v) {
            $i++;
            $this->write('=' . $k . '=' . $v, ($i === $count));
        }
        return $this->read();
    }

    public function write($command, $param2 = true)
    {
        if (!$this->socket) return;
        $data = explode("\n", $command);
        foreach ($data as $com) {
            $com = trim($com);
            @fwrite($this->socket, $this->encodeLength(strlen($com)) . $com);
        }
        if ($param2) {
            @fwrite($this->socket, chr(0));
        }
    }

    public function read($parse = true)
    {
        $RESPONSE = [];
        if (!$this->socket) return $RESPONSE;

        while (true) {
            $r = [$this->socket];
            $w = null;
            $e = null;
            // Batas tunggu pembacaan buffer maksimal 1 detik
            $num_changed_streams = @stream_select($r, $w, $e, 1);

            if ($num_changed_streams === false || $num_changed_streams === 0) {
                break;
            }

            $byte = @fread($this->socket, 1);
            if ($byte === false || strlen($byte) === 0) {
                break;
            }

            $byte = ord($byte);
            $length = 0;
            if ($byte & 128) {
                if (($byte & 192) === 128) {
                    $length = (($byte & 63) << 8) + ord(fread($this->socket, 1));
                } elseif (($byte & 224) === 192) {
                    $length = (($byte & 31) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
                } elseif (($byte & 240) === 224) {
                    $length = (($byte & 15) << 24) + (ord(fread($this->socket, 1)) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
                } else {
                    $length = ord(fread($this->socket, 1));
                }
            } else {
                $length = $byte;
            }

            $_ = '';
            if ($length > 0) {
                $ret = 0;
                while ($ret < $length) {
                    $s = fread($this->socket, $length - $ret);
                    if ($s === false || strlen($s) === 0) break;
                    $ret += strlen($s);
                    $_ .= $s;
                }
            }

            if ($_ !== '') {
                $RESPONSE[] = $_;
            }

            if ($_ === '!done' || $_ === '!trap' || $_ === '!fatal') {
                break;
            }
        }

        if ($parse) {
            $PARSED = [];
            $CURRENT = [];
            foreach ($RESPONSE as $x) {
                if (in_array($x, ['!fatal', '!done', '!trap'])) {
                    if (!empty($CURRENT)) {
                        $PARSED[] = $CURRENT;
                        $CURRENT = [];
                    }
                    continue;
                }
                if ($x === '!re') {
                    if (!empty($CURRENT)) {
                        $PARSED[] = $CURRENT;
                        $CURRENT = [];
                    }
                    continue;
                }
                if (preg_match_all('/^=([^=]+)=(.*)$/is', $x, $matches)) {
                    $CURRENT[$matches[1][0]] = $matches[2][0];
                }
            }
            return $PARSED;
        }
        return $RESPONSE;
    }

    private function encodeLength($length)
    {
        if ($length < 0x80) {
            return chr($length);
        } elseif ($length < 0x4000) {
            return chr(($length >> 8) | 0x80) . chr($length & 0xFF);
        } elseif ($length < 0x200000) {
            return chr(($length >> 16) | 0xC0) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } elseif ($length < 0x10000000) {
            return chr(($length >> 24) | 0xE0) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } else {
            return chr(0xF0) . chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        }
    }
}