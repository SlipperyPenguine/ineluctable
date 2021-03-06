<?php
/*
The MIT License (MIT)

Copyright (c) 2014 eve-seat

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

namespace ineluctable\EveApi\Character;

use ineluctable\EveApi\BaseApi;
use ineluctable\models\EveCharacterStandingsAgents;
use ineluctable\models\EveCharacterStandingsFactions;
use ineluctable\models\EveCharacterStandingsNPCCorporations;
use Pheal\Exceptions\APIException;
use Pheal\Exceptions\PhealException;
use Pheal\Pheal;

class Standings extends BaseApi
{

    public static function Update($keyID, $vCode)
    {

        // Start and validate they key pair
        BaseApi::bootstrap();
        BaseApi::validateKeyPair($keyID, $vCode);

        // Set key scopes and check if the call is banned
        $scope = 'Char';
        $api = 'Standings';

        if (BaseApi::isBannedCall($api, $scope, $keyID))
            return;

        // Get the characters for this key
        $characters = BaseApi::findKeyCharacters($keyID);

        // Check if this key has any characters associated with it
        if (!$characters)
            return;

        // Lock the call so that we are the only instance of this running now()
        // If it is already locked, just return without doing anything
        if (!BaseApi::isLockedCall($api, $scope, $keyID))
            $lockhash = BaseApi::lockCall($api, $scope, $keyID);
        else
            return;

        // Next, start our loop over the characters and upate the database
        foreach ($characters as $characterID) {

            // Prepare the Pheal instance
            $pheal = new Pheal($keyID, $vCode);

            // Do the actual API call. pheal-ng actually handles some internal
            // caching too.
            try {

                $standings = $pheal
                    ->charScope
                    ->Standings(array('characterID' => $characterID));

            } catch (APIException $e) {

                // If we cant get account status information, prevent us from calling
                // this API again
                BaseApi::banCall($api, $scope, $keyID, 0, $e->getCode() . ': ' . $e->getMessage());
                return;

            } catch (PhealException $e) {

                throw $e;
            }

            // Check if the data in the database is still considered up to date.
            // checkDbCache will return true if this is the case
            if (!BaseApi::checkDbCache($scope, $api, $standings->cached_until, $characterID)) {

                // Populate the agents standings for this character
                foreach ($standings->characterNPCStandings->agents as $standing) {

                    $standing_data = EveCharacterStandingsAgents::where('characterID', '=', $characterID)
                        ->where('fromID', '=', $standing->fromID)
                        ->first();

                    if (!$standing_data)
                        $standing_data = new EveCharacterStandingsAgents;

                    $standing_data->characterID = $characterID;
                    $standing_data->fromID = $standing->fromID;
                    $standing_data->fromName = $standing->fromName;
                    $standing_data->standing = $standing->standing;
                    $standing_data->save();
                }

                // Populate the faction standings for this character
                foreach ($standings->characterNPCStandings->factions as $standing) {

                    $standing_data = EveCharacterStandingsFactions::where('characterID', '=', $characterID)
                        ->where('fromID', '=', $standing->fromID)
                        ->first();

                    if (!$standing_data)
                        $standing_data = new EveCharacterStandingsFactions;

                    $standing_data->characterID = $characterID;
                    $standing_data->fromID = $standing->fromID;
                    $standing_data->fromName = $standing->fromName;
                    $standing_data->standing = $standing->standing;
                    $standing_data->save();
                }

                // Populate the NPCCorporation standings for this character
                foreach ($standings->characterNPCStandings->NPCCorporations as $standing) {

                    $standing_data = EveCharacterStandingsNPCCorporations::where('characterID', '=', $characterID)
                        ->where('fromID', '=', $standing->fromID)
                        ->first();

                    if (!$standing_data)
                        $standing_data = new EveCharacterStandingsNPCCorporations;

                    $standing_data->characterID = $characterID;
                    $standing_data->fromID = $standing->fromID;
                    $standing_data->fromName = $standing->fromName;
                    $standing_data->standing = $standing->standing;
                    $standing_data->save();
                }

                // Update the cached_until time in the database for this api call
                BaseApi::setDbCache($scope, $api, $standings->cached_until, $characterID);
            }
        }

        // Unlock the call
        BaseApi::unlockCall($lockhash);

        return $standings;
    }
}
