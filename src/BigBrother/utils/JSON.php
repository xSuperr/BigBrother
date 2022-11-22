<?php

namespace BigBrother\utils;

use InvalidArgumentException;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\utils\TextFormat;

class JSON
{
    // TODO: Rewrite these
    /**
     * @param string|null $message
     * @param int         $type
     * @param array|null  $parameters
     * @return string
     */
    public static function toJSON(?string $message, int $type = 1, ?array $parameters = []) : string{
        $result = json_decode(self::toJSONInternal($message), true);

        switch($type){
            case TextPacket::TYPE_TRANSLATION:
                unset($result["text"]);
                $message = TextFormat::clean($message);

                if(substr($message, 0, 1) === "["){//chat.type.admin
                    $result["translate"] = "chat.type.admin";
                    $result["color"] = "gray";
                    $result["italic"] = true;
                    unset($result["extra"]);

                    $result["with"][] = ["text" => substr($message, 1, strpos($message, ":") - 1)];

                    if($message === "[CONSOLE: Reload complete.]" or $message === "[CONSOLE: Reloading server...]"){//blame pmmp
                        $result["with"][] = ["translate" => substr(substr($message, strpos($message, ":") + 2), 0, - 1), "color" => "yellow"];
                    }else{
                        $result["with"][] = ["translate" => substr(substr($message, strpos($message, ":") + 2), 0, - 1)];
                    }

                    $with = &$result["with"][1];
                }else{
                    $result["translate"] = str_replace("%", "", $message);

                    $with = &$result;
                }

                foreach($parameters as $parameter){
                    if(strpos($parameter, "%") !== false){
                        $with["with"][] = ["translate" => str_replace("%", "", $parameter)];
                    }else{
                        $with["with"][] = ["text" => $parameter];
                    }
                }
                break;
            case TextPacket::TYPE_POPUP:
            case TextPacket::TYPE_TIP://Just to be sure
                if(isset($result["text"])){
                    $result["text"] = str_replace("\n", "", $message);
                }

                if(isset($result["extra"])){
                    unset($result["extra"]);
                }
                break;
        }

        if(isset($result["extra"])){
            if(count($result["extra"]) === 0){
                unset($result["extra"]);
            }
        }

        $result = json_encode($result, JSON_UNESCAPED_SLASHES);
        return $result;
    }

    /**
     * Returns an JSON-formatted string with colors/markup
     *
     * @internal
     * @param string|string[] $string
     * @return string
     */
    public static function toJSONInternal(mixed $string) : string{
        if(!is_array($string)){
            $string = TextFormat::tokenize($string);
        }
        $newString = [];
        $pointer =& $newString;
        $color = "white";
        $bold = false;
        $italic = false;
        $underlined = false;
        $strikethrough = false;
        $obfuscated = false;
        $index = 0;

        foreach($string as $token){
            if(isset($pointer["text"])){
                if(!isset($newString["extra"])){
                    $newString["extra"] = [];
                }
                $newString["extra"][$index] = [];
                $pointer =& $newString["extra"][$index];
                if($color !== "white"){
                    $pointer["color"] = $color;
                }
                if($bold){
                    $pointer["bold"] = true;
                }
                if($italic){
                    $pointer["italic"] = true;
                }
                if($underlined){
                    $pointer["underlined"] = true;
                }
                if($strikethrough){
                    $pointer["strikethrough"] = true;
                }
                if($obfuscated){
                    $pointer["obfuscated"] = true;
                }
                ++$index;
            }
            switch($token){
                case TextFormat::BOLD:
                    if(!$bold){
                        $pointer["bold"] = true;
                        $bold = true;
                    }
                    break;
                case TextFormat::OBFUSCATED:
                    if(!$obfuscated){
                        $pointer["obfuscated"] = true;
                        $obfuscated = true;
                    }
                    break;
                case TextFormat::ITALIC:
                    if(!$italic){
                        $pointer["italic"] = true;
                        $italic = true;
                    }
                    break;
                case TextFormat::UNDERLINE:
                    if(!$underlined){
                        $pointer["underlined"] = true;
                        $underlined = true;
                    }
                    break;
                case TextFormat::STRIKETHROUGH:
                    if(!$strikethrough){
                        $pointer["strikethrough"] = true;
                        $strikethrough = true;
                    }
                    break;
                case TextFormat::RESET:
                    if($color !== "white"){
                        $pointer["color"] = "white";
                        $color = "white";
                    }
                    if($bold){
                        $pointer["bold"] = false;
                        $bold = false;
                    }
                    if($italic){
                        $pointer["italic"] = false;
                        $italic = false;
                    }
                    if($underlined){
                        $pointer["underlined"] = false;
                        $underlined = false;
                    }
                    if($strikethrough){
                        $pointer["strikethrough"] = false;
                        $strikethrough = false;
                    }
                    if($obfuscated){
                        $pointer["obfuscated"] = false;
                        $obfuscated = false;
                    }
                    break;

                //Colors
                case TextFormat::BLACK:
                    $pointer["color"] = "black";
                    $color = "black";
                    break;
                case TextFormat::DARK_BLUE:
                    $pointer["color"] = "dark_blue";
                    $color = "dark_blue";
                    break;
                case TextFormat::DARK_GREEN:
                    $pointer["color"] = "dark_green";
                    $color = "dark_green";
                    break;
                case TextFormat::DARK_AQUA:
                    $pointer["color"] = "dark_aqua";
                    $color = "dark_aqua";
                    break;
                case TextFormat::DARK_RED:
                    $pointer["color"] = "dark_red";
                    $color = "dark_red";
                    break;
                case TextFormat::DARK_PURPLE:
                    $pointer["color"] = "dark_purple";
                    $color = "dark_purple";
                    break;
                case TextFormat::GOLD:
                    $pointer["color"] = "gold";
                    $color = "gold";
                    break;
                case TextFormat::GRAY:
                    $pointer["color"] = "gray";
                    $color = "gray";
                    break;
                case TextFormat::DARK_GRAY:
                    $pointer["color"] = "dark_gray";
                    $color = "dark_gray";
                    break;
                case TextFormat::BLUE:
                    $pointer["color"] = "blue";
                    $color = "blue";
                    break;
                case TextFormat::GREEN:
                    $pointer["color"] = "green";
                    $color = "green";
                    break;
                case TextFormat::AQUA:
                    $pointer["color"] = "aqua";
                    $color = "aqua";
                    break;
                case TextFormat::RED:
                    $pointer["color"] = "red";
                    $color = "red";
                    break;
                case TextFormat::LIGHT_PURPLE:
                    $pointer["color"] = "light_purple";
                    $color = "light_purple";
                    break;
                case TextFormat::YELLOW:
                    $pointer["color"] = "yellow";
                    $color = "yellow";
                    break;
                case TextFormat::WHITE:
                    $pointer["color"] = "white";
                    $color = "white";
                    break;
                default:
                    $pointer["text"] = $token;
                    break;
            }
        }

        if(isset($newString["extra"])){
            foreach($newString["extra"] as $k => $d){
                if(!isset($d["text"])){
                    unset($newString["extra"][$k]);
                }
            }
        }

        $result = json_encode($newString, JSON_UNESCAPED_SLASHES);
        if($result === false){
            throw new InvalidArgumentException("Failed to encode result JSON: " . json_last_error_msg());
        }
        return $result;
    }
}