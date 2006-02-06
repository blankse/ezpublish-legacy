<?php
//
// Definition of eZTranslatorManager class
//
// Created on: <10-Jun-2002 11:16:48 amos>
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ publish
// SOFTWARE RELEASE: 3.5.x
// COPYRIGHT NOTICE: Copyright (C) 1999-2006 eZ systems AS
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//

/*! \file eztranslatormanager.php
*/

/*! \defgroup eZTranslation Translation
    \ingroup eZI18N
*/


/*!
  \class eZTranslatorManager eztranslatormanager.php
  \ingroup eZTranslation
  \brief This provides internationalization support for text output

  Each message consists of:
   - context - the context of the translation
   - source - the source string
   - comment - a variation of the context/source
   - key - the uniquely generated key taken from context, source and eventually comment


*/

include_once( "lib/ezi18n/classes/eztranslatorhandler.php" );

class eZTranslatorManager
{
    /*!
    */
    function eZTranslatorManager()
    {
        $this->Handlers = array();
    }

    /*!
     Tries to find the translation message that matches \a $key in all it's handlers
     and returns it. If no message could be found it either means that none of the
     handlers have a translation for the key or that some of the handlers are not key based,
     for instance realtime translators.
     In the latter case an extra call to findMessage() or translate() is required.

     Use keyTranslate if you only want to translate a message.

     \sa findMessage, keyTranslate
    */
    function &findKey( $key )
    {
        $msg = null;
        for ( $i = 0; $i < count( $this->Handlers ) and $msg === null; ++$i )
        {
            $handler =& $this->Handlers[$i];
            if ( $handler->isKeyBased() )
                $msg =& $handler->findKey( $key );
        }
        return $msg;
    }

    /*!
     Tries to find the translation message that matches \a $context, \a $source and
     \a $comment. If that fails it tries \a $context and \a $source only.
     The message is then returned or null if no translation message could be found/generated for it.

     Use translate if you only want to translate a message.

     \sa findKey, translate
    */
    function &findMessage( $context, $source, $comment = null )
    {
        if ( !is_string( $context ) or $context == "" )
            $context = "default";
        $msg = null;
        for ( $i = 0; $i < count( $this->Handlers ) and $msg === null; ++$i )
        {
            $handler =& $this->Handlers[$i];
            $msg =& $handler->findMessage( $context, $source, $comment );
        }
        return $msg;
    }

    /*!
     \return the translation string for \a $key.

     Note this returns the exact translation for the given key, use translate()
     instead if you want to have variable comment support.

     \sa findKey, translate
    */
    function &keyTranslate( $key )
    {
        $trans = null;
        for ( $i = 0; $i < count( $this->Handlers ) and $trans === null; ++$i )
        {
            $handler =& $this->Handlers[$i];
            if ( $handler->isKeyBased() )
                $trans =& $handler->keyTranslate( $key );
        }
        return $trans;
    }

    /*!
     \return the translation string for \a $source and \a $context or null if the key does not exist.

     \sa findMessage, findKey
    */
    function &translate( $context, $source, $comment = null )
    {
        if ( !is_string( $context ) or $context == "" )
            $context = "default";
        $trans = null;
        for ( $i = 0; $i < count( $this->Handlers ) and $trans === null; ++$i )
        {
            $handler =& $this->Handlers[$i];
            $trans =& $handler->translate( $context, $source, $comment );
        }
        return $trans;
    }

    /*!
     \static
     \return the unique instance of the translator system.
    */
    function &instance()
    {
        $instance =& $GLOBALS["eZTranslatorManagerInstance"];
        if ( get_class( $instance ) != "eztranslatormanager" )
        {
            $instance = new eZTranslatorManager();
        }
        return $instance;
    }

    /*!
     \static
     Registers the handler object \a $handler.
    */
    function registerHandler( &$handler )
    {
        if ( isset( $this ) and get_class( $this ) == "eztranslatormanager" )
            $instance =& $this;
        else
            $instance =& eZTranslatorManager::instance();
        $instance->Handlers[] =& $handler;
    }

    /*!
     \static
     Creates an md5 key based on the \a $context, \a $source and \a $comment and returns it.
    */
    function createKey( $context, $source, $comment = null )
    {
        if ( $comment === null )
            $comment = "";
        return md5( "$context\n$source\n$comment" );
    }

    /*!
     \static
     Creates a message structure out of \a $context, \a $source and \a $comment
     and returns it.
    */
    function &createMessage( $context, $source, $comment = null, $translation = null )
    {
        $msg = array( "context" => $context,
                      "source" => $source,
                      "comment" => $comment,
                      "translation" => $translation );
        return $msg;
    }

    /// \privatesection
    /// The array of handler objects
    var $Handlers;
}

?>
