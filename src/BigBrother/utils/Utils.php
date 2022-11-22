<?php

namespace BigBrother\utils;

use BigBrother\nbt\IO\Reader\GZipCompressedStringReader;
use BigBrother\nbt\NbtFormat;
use BigBrother\nbt\Tag\CompoundTag;
use BigBrother\nbt\Tag\Tag;
use BigBrother\BigBrother;
use RuntimeException;

class Utils
{
    public static function callDirectory(string $directory, callable $callable): void{
        $main = explode("\\", BigBrother::getInstance()->getDescription()->getMain());
        unset($main[array_key_last($main)]);
        $main = implode("/", $main);
        $directory = rtrim(str_replace(DIRECTORY_SEPARATOR, "/", $directory), "/");
        $dir = BigBrother::getInstance()->getFile() . "src/$main/" . $directory;

        foreach(array_diff(scandir($dir), [".", ".."]) as $file){
            $path = $dir . "/$file";
            $extension = pathinfo($path)["extension"] ?? null;

            if($extension === null){
                self::callDirectory($directory . "/" . $file, $callable);
            }elseif($extension === "php"){
                $namespaceDirectory = str_replace("/", "\\", $directory);
                $namespaceMain = str_replace("/", "\\", $main);
                $namespace = $namespaceMain . "\\$namespaceDirectory\\" . basename($file, ".php");
                $callable($namespace);
            }
        }
    }

    public static function formalize(string $identifier): string
    {
        return str_contains($identifier, ":") ? "minecraft:" . $identifier : $identifier;
    }

    public static function loadCompoundFromFile(string $filePath) : CompoundTag{
        $rawNbt = @file_get_contents($filePath);
        if($rawNbt === false) throw new RuntimeException("Failed to read file");
        $reader = new GZipCompressedStringReader($rawNbt, NbtFormat::JAVA_EDITION);
        $tag = Tag::load($reader);
        if(!$tag instanceof CompoundTag) throw new RuntimeException("Tag is not a compound tag");
        return $tag;
    }
}