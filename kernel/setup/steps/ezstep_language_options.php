<?php
//
// Definition of eZStepLanguageOptions class
//
// Created on: <11-Aug-2003 17:27:57 kk>
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

/*! \file ezstep_language_options.php
*/
include_once( 'kernel/setup/steps/ezstep_installer.php' );
include_once( 'kernel/setup/ezsetupcommon.php' );
include_once( "kernel/common/i18n.php" );

/*!
  \class eZStepLanguageOptions ezstep_language_options.php
  \brief The class eZStepLanguageOptions does

*/

class eZStepLanguageOptions extends eZStepInstaller
{
    /*!
     Constructor
     \reimp
    */
    function eZStepLanguageOptions(&$tpl, &$http, &$ini, &$persistenceList )
    {
        $this->eZStepInstaller( $tpl, $http, $ini, $persistenceList,
                                'language_options', 'Language options' );
    }

    /*!
     \reimp
     */
    function processPostData()
    {
        $primaryLanguage = $this->Http->postVariable( 'eZSetupDefaultLanguage' );
        $languages       = $this->Http->hasPostVariable( 'eZSetupLanguages' ) ? $this->Http->postVariable( 'eZSetupLanguages' ): array();

        if ( !in_array( $primaryLanguage, $languages ) )
            $languages[] = $primaryLanguage;

        $regionalInfo = array();
        $regionalInfo['language_type'] = 1 ;
        $regionalInfo['primary_language'] = $primaryLanguage;
        $regionalInfo['languages'] = $languages;
        $this->PersistenceList['regional_info'] = $regionalInfo;

        if ( !isset( $this->PersistenceList['database_info']['use_unicode'] ) ||
             $this->PersistenceList['database_info']['use_unicode'] == false )
        {
            // If we have already figured out charset and it is utf-8
            // we don't have to check the new languages
            if ( isset( $this->PersistenceList['regional_info']['site_charset'] ) and
                 $this->PersistenceList['regional_info']['site_charset'] == 'utf-8' )
            {
                $charset = 'utf-8';
            }
            else
            {
                include_once( 'lib/ezlocale/classes/ezlocale.php' );
                $primaryLanguage     = null;
                $allLanguages        = array();
                $allLanguageCodes    = array();
                $variationsLanguages = array();
                $primaryLanguageCode = $this->PersistenceList['regional_info']['primary_language'];
                $extraLanguageCodes  = isset( $this->PersistenceList['regional_info']['languages'] ) ? $this->PersistenceList['regional_info']['languages'] : array();
                $extraLanguageCodes  = array_diff( $extraLanguageCodes, array( $primaryLanguageCode ) );

                /*
                if ( isset( $this->PersistenceList['regional_info']['variations'] ) )
                {
                    $variations = $this->PersistenceList['regional_info']['variations'];
                    foreach ( $variations as $variation )
                    {
                        $locale = eZLocale::create( $variation );
                        if ( $locale->localeCode() == $primaryLanguageCode )
                        {
                            $primaryLanguage = $locale;
                        }
                        else
                        {
                            $variationsLanguages[] = $locale;
                        }
                    }
                }
                */

                if ( $primaryLanguage === null )
                    $primaryLanguage = eZLocale::create( $primaryLanguageCode );

                $allLanguages[] =& $primaryLanguage;

                foreach ( $extraLanguageCodes as $extraLanguageCode )
                {
                    $allLanguages[] =& eZLocale::create( $extraLanguageCode );
                    $allLanguageCodes[] = $extraLanguageCode;
                }

                $charset = $this->findAppropriateCharset( $primaryLanguage, $allLanguages, false );

                if ( !$charset )
                {
                    $this->Error = 1;
                    return false;
                }
            }
            // Store the charset for later handling
            $this->PersistenceList['regional_info']['site_charset'] = $charset;
        }

        return true;
    }

    /*!
      \reimp
     */
    function init()
    {
        if ( $this->hasKickstartData() )
        {
            $data = $this->kickstartData();

            $regionalInfo = array();
            $regionalInfo['primary_language'] = $data['Primary'];
            if ( !in_array( $data['Primary'], $data['Languages'] ) )
                $data['Languages'][] = $data['Primary'];
            $regionalInfo['languages'] = $data['Languages'];
            $this->PersistenceList['regional_info'] = $regionalInfo;

            return $this->kickstartContinueNextStep();
        }

        return false;
    }

    /*!
     \reimp
     */
    function display()
    {
        $locales =& eZLocale::localeList( true );
        $languages = array();
        foreach ( array_keys( $locales ) as $localeKey )
        {
            $locale =& $locales[$localeKey];
            if ( !$locale->attribute( 'country_variation' ) )
                $languages[] = $locale;
        }

        // bubble sort language based on language name. bubble bad, but only about 8-9 elements
        for ( $i =0; $i < count( $languages ); $i++ )
            for ( $n = 0; $n < count( $languages ) - 1; $n++ )
            {
                if ( strcmp( $languages[$n]['language_name'], $languages[$n+1]['language_name'] ) > 0 )
                {
                    $tmpElement = $languages[$n];
                    $languages[$n] = $languages[$n+1];
                    $languages[$n+1] = $tmpElement;
                }
            }

        $this->Tpl->setVariable( 'language_list', $languages );
        $showUnicodeError = false;
        if ( isset( $this->Error ) )
        {
            $showUnicodeError = !$this->PersistenceList['database_info']['use_unicode'];
            unset( $this->PersistenceList['database_info']['use_unicode'] );
        }
        $this->Tpl->setVariable( 'show_unicode_error', $showUnicodeError );

        $regionalInfo = array( 'primary_language' => 'eng-GB',
                               'languages' => array() );
        if ( isset( $this->PersistenceList['regional_info'] ) )
            $regionalInfo = $this->PersistenceList['regional_info'];

        $this->Tpl->setVariable( 'regional_info', $regionalInfo );

        $result = array();
        // Display template

        $result['content'] = $this->Tpl->fetch( "design:setup/init/language_options.tpl" );
        $result['path'] = array( array( 'text' => ezi18n( 'design/standard/setup/init',
                                                          'Language options' ),
                                        'url' => false ) );
        return $result;
    }


    var $Error;
}

?>
