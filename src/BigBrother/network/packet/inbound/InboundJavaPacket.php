<?php

declare(strict_types=1);

namespace BigBrother\network\packet\inbound;

use BigBrother\network\JavaNetworkSession;
use pocketmine\network\mcpe\protocol\DataPacket;

/**
 * Also known as server bound packets (client -> server)
 */
interface InboundJavaPacket
{
    //Play
    const TELEPORT_CONFIRM = 0x00;
    const QUERY_BLOCK_ENTITY_TAG = 0x01;
    const CHANGE_DIFFICULTY = 0x02;
    const MESSAGE_ACKNOWLEDGEMENT = 0x03;
    const CHAT_COMMAND = 0x04;
    const CHAT_MESSAGE = 0x05;
    const CHAT_PREVIEW = 0x06;
    const CLIENT_COMMAND = 0x07;
    const CLIENT_INFORMATION = 0x08;
    const COMMAND_SUGGESTIONS_REQUEST = 0x09;
    const CLICK_CONTAINER_BUTTON = 0x0A;
    const CLICK_CONTAINER = 0x0B;
    const CLOSE_CONTAINER = 0x0C;
    const PLUGIN_MESSAGE = 0x0D;
    const EDIT_BOOK = 0x0E;
    const QUERY_ENTITY_TAG = 0x0F;
    const INTERACT = 0x10;
    const JIGSAW_GENERATE = 0x11;
    const KEEP_ALIVE = 0x12;
    const LOCK_DIFFICULTY = 0x13;
    const SET_PLAYER_POSITION = 0x14;
    const SET_PLAYER_POSITION_ROTATION = 0x15;
    const SET_PLAYER_ROTATION = 0x16;
    const SET_PLAYER_ON_GROUND = 0x17;
    const MOVE_VEHICLE = 0x18;
    const PADDLE_BOAT = 0x19;
    const PICK_ITEM = 0x1A;
    const PLACE_RECIPE = 0x1B;
    const PLAYER_ABILITIES = 0x1C;
    const PLAYER_ACTION = 0x1D;
    const PLAYER_COMMAND = 0x1E;
    const PLAYER_INPUT = 0x1F;
    const PONG = 0x20;
    const CHANGE_RECIPE_BOOK_SETTINGS = 0x21;
    const SET_SEEN_RECIPE = 0x22;
    const RENAME_ITEM = 0x23;
    const RESOURCE_PACK = 0x24;
    const SEEN_ADVANCEMENTS = 0x25;
    const SELECT_TRADE = 0x26;
    const SET_BEACON_EFFECT = 0x27;
    const SET_HELD_ITEM = 0x28;
    const PROGRAM_COMMAND_BLOCK = 0x29;
    const PROGRAM_COMMAND_BLOCK_MINECRAFT = 0x2A;
    const SET_CREATIVE_MODE_SLOT = 0x2B;
    const PROGRAM_JIGSAW_BLOCK = 0x2C;
    const PROGRAM_STRUCTURE_BLOCK = 0x2D;
    const UPDATE_SIGN = 0x2E;
    const SWING_ARM = 0x2F;
    const TELEPORT_TO_ENTITY = 0x30;
    const USE_ITEM_ON = 0x31;
    const USE_ITEM = 0x32;

    //Handshake
    const HANDSHAKE = 0x00;
    const LEGACY_SERVER_LIST_PING = 0xFE;

    //Status
    const STATUS_REQUEST = 0x00;
    const PING_REQUEST = 0x01;

    //Login
    const LOGIN_START = 0x00;
    const ENCRYPTION_RESPONSE = 0x01;
    const LOGIN_PLUGIN_RESPONSE = 0x02;

    /** @return null|DataPacket|DataPacket[] */
    public function fromJava(JavaNetworkSession $session): null|DataPacket|array;

}