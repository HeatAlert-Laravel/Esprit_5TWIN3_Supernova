<?php

namespace Database\Seeders;

use App\Models\AdviceDocument;

final class AdviceArticleContent
{
    public static function document(string $title): array
    {
        [$intro, $heading, $items, $nextHeading, $next, $tip] = self::articles()[$title];
        $ops = [
            ['insert' => $intro."\n"],
            ['insert' => $heading], ['insert' => "\n", 'attributes' => ['header' => 2]],
        ];
        foreach ($items as $item) {
            $ops[] = ['insert' => $item];
            $ops[] = ['insert' => "\n", 'attributes' => ['list' => 'bullet']];
        }
        $ops[] = ['insert' => $nextHeading];
        $ops[] = ['insert' => "\n", 'attributes' => ['header' => 2]];
        $ops[] = ['insert' => $next."\n"];
        $ops[] = ['insert' => 'Keep in mind: ', 'attributes' => ['bold' => true]];
        $ops[] = ['insert' => $tip."\n"];

        return AdviceDocument::normalize(['ops' => $ops]);
    }

    private static function articles(): array
    {
        return [
            'Make a simple plan for hot days' => [
                'A useful household plan does not need to be long. Start with the information you would otherwise have to look up in a hurry, and keep it somewhere everyone can find it.',
                'Put the essentials in one place', [
                    'Check the weather alerts for your neighborhood and note any planned power outages.',
                    'Write down a trusted contact and a backup contact, including how to reach them.',
                    'Choose a nearby cooling point and check its opening hours and accessibility.',
                ],
                'Walk through the plan together',
                'Talk through what you would do if your usual arrangements changed. Decide who would make contact, where you could meet, and which information should be available on paper. Review these choices when household needs or local services change.',
                'Choose a plan that fits your household rather than trying to prepare for every possible situation.',
            ],
            'Keep your essentials easy to find' => [
                'During a power interruption, even simple tasks are easier when useful items are close by. A small, familiar place for essentials can save a search in an unlit room.',
                'Prepare an easy-to-reach spot', [
                    'Keep a working torch where you can reach it and check its batteries.',
                    'Put your important contact numbers on paper, alongside your household plan.',
                    'Check your phone and power bank before a planned interruption.',
                ],
                'Make the arrangement work for everyone',
                'Show other members of the household where the items are stored. Choose a location that is accessible without moving heavy objects or blocking a walkway. If an item is borrowed, return it to the same place so the next person can find it.',
                'Keep the collection small enough to check and maintain regularly.',
            ],
            'Choose quieter activities for hot afternoons' => [
                'Planning a few calm activities gives your family options when outdoor plans change. Choose things you already enjoy and that are easy to move to a more comfortable room.',
                'Build a small activity basket', [
                    'Include books, paper, pencils, or a familiar quiet game.',
                    'Choose at least one activity that does not need a screen or electricity.',
                    'Let children help choose the activities so the alternatives feel familiar.',
                ],
                'Keep the plan flexible',
                'Agree on an alternative place to spend time if your usual room becomes uncomfortable or the power goes out. Check local cooling-point information before travelling, and make sure the activity supplies can be put away without blocking a path.',
                'The aim is to give everyone options, not to fill the afternoon with a strict schedule.',
            ],
            'Know your equipment before a power cut' => [
                'Equipment needs can differ between households. Preparing a short equipment list helps you find the right instructions and support contacts before an interruption happens.',
                'Make a reference list', [
                    'List the equipment that your household relies on and identify its manufacturer or provider.',
                    'Keep the relevant interruption and safe-restarting instructions somewhere accessible.',
                    'Write down support numbers and the person responsible for each arrangement.',
                ],
                'Confirm essential arrangements',
                'If equipment is essential for someone’s care, agree on an interruption plan with the relevant professional or provider in advance. Ask them to explain any arrangement that is unclear, and share the agreed information with the people who need it.',
                'Use the instructions for your actual equipment; general advice cannot replace its provider’s guidance.',
            ],
            'Prepare a comfortable room' => [
                'Knowing how sunlight moves through your home can help you choose where to spend time on a hot day. Start by noticing which rooms receive direct sun and when.',
                'Look around your living space', [
                    'Identify the windows that receive the strongest direct sunlight.',
                    'Check which curtains or blinds can shade those windows.',
                    'Keep pathways clear and make frequently used items easy to reach.',
                ],
                'Choose a backup place',
                'Agree on a comfortable room where the household can gather. Keep details of a nearby cooling point available in case conditions at home become uncomfortable. Before leaving, confirm the destination’s hours, location, and any access requirements.',
                'Revisit the arrangement when your household’s needs or the weather conditions change.',
            ],
            'Keep an offline copy of your household plan' => [
                'A phone is useful, but your household plan should also be available when a battery is flat or a connection is unavailable. A paper copy makes the essentials easier to share.',
                'Include information you can use quickly', [
                    'Write the main household contacts and an alternative contact.',
                    'Add equipment support numbers and your agreed meeting place.',
                    'Note the location and opening times of useful local places.',
                ],
                'Keep copies easy to find',
                'Store one copy in a familiar place at home and consider another for the person who usually travels with the household. Check that the writing is clear and readable. Update the copy whenever a contact number or an agreed arrangement changes.',
                'Include only the personal information that is needed for the plan.',
            ],
            'Agree on a check-in with someone you trust' => [
                'A regular check-in can make it easier to stay connected when a hot day changes your routine. Agree on an arrangement together so each person knows what to expect.',
                'Agree on the details', [
                    'Choose a relative, friend, or neighbor you are comfortable contacting.',
                    'Decide on a convenient time and a usual way to make contact.',
                    'Choose a backup method if a phone is unavailable.',
                ],
                'Explain what happens if plans change',
                'Let the other person know if you will be out or if the agreed time no longer works. Discuss who else can be contacted if needed, and keep those details easy to find. Share only information you are comfortable sharing.',
                'A clear, simple arrangement is easier to follow than one with many steps.',
            ],
            'Build a shared family contact card' => [
                'A contact card keeps the family’s most useful information together. Creating it as a household also gives everyone a chance to ask questions about the plan.',
                'Create the card together', [
                    'Include the main household contacts and a trusted alternative adult.',
                    'Add the agreed meeting place in clear, familiar language.',
                    'Keep a readable copy in a place everyone can identify.',
                ],
                'Practise finding the information',
                'Talk through a simple example, such as a phone being unavailable or an activity ending earlier than expected. Make sure children know which trusted adult they can approach. Review the card when contact details or family arrangements change.',
                'Keep the information practical and avoid adding details the household does not need.',
            ],
            'Review a care plan before an interruption' => [
                'The best preparation starts with the person you support. Ask which daily arrangements matter most to them and which information would be useful if a routine changed.',
                'Check the agreed arrangements', [
                    'Confirm the relevant care and equipment support contacts.',
                    'Review any interruption arrangements with the care provider.',
                    'Make the agreed plan accessible to the people who need it.',
                ],
                'Keep the person involved',
                'Explain the plan in a way that is comfortable for the person and confirm who can make contact on their behalf. Review unclear instructions with the provider rather than guessing. Update the shared information when support arrangements change.',
                'Respect the person’s preferences and share only the information needed for their support.',
            ],
            'Find a cooling place before you leave' => [
                'Looking up a cooling place before travelling gives you time to choose a destination that fits your needs. Check the practical details rather than relying only on a place name.',
                'Check the destination', [
                    'Browse HeatAlert’s cooling points and choose a place along a suitable route.',
                    'Confirm its opening hours and any accessibility information.',
                    'Keep another nearby option in mind in case the first place is unavailable.',
                ],
                'Make the journey easier to plan',
                'Check transport information and keep the address accessible. If useful, tell someone where you are going and how they can reach you. Recheck the destination’s details if your departure time changes or you have not visited recently.',
                'Information can change; confirm important details before setting off.',
            ],
            'Plan a route with places to pause' => [
                'A route with familiar places to stop gives you more options when a hot day affects your journey. Prepare an alternative rather than assuming your usual route will suit every day.',
                'Look at the route before leaving', [
                    'Identify shaded stops or public places where you could pause.',
                    'Check local weather alerts and any transport changes.',
                    'Keep the address of a nearby cooling point available.',
                ],
                'Allow room to change your plans',
                'Choose a route and schedule that can be adjusted if conditions change. If travelling with others, discuss where you could meet or pause together. Keep essential contact details available in case your usual way of staying in touch is unavailable.',
                'A plan should help you make adjustments, not force you to follow an uncomfortable route.',
            ],
            'Neighborhood preparation checklist' => [
                'This draft is a starting point for the group to review. It brings useful neighborhood information together, but each item needs to be checked before the article is published.',
                'Check the local information', [
                    'List useful public places and confirm their opening hours.',
                    'Review accessibility and transport information with the group.',
                    'Agree which contact details are appropriate to share publicly.',
                ],
                'Review before publishing',
                'Assign someone to check each part of the list and record what still needs confirmation. Remove information that cannot be verified. Once the group agrees the content is accurate and readable, preview the resident page before changing the article to published.',
                'Keep this article as a draft until the team has reviewed the details.',
            ],
        ];
    }
}
