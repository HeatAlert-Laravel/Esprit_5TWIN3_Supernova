<?php

namespace Database\Seeders;

use App\Models\CategorieConseil;
use App\Models\Conseil;
use Illuminate\Database\Seeder;

class ConseilSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CategorieConseilSeeder::class);

        // Demo content is deliberately practical and can be edited by the team.
        $articles = [
            ['Everyday preparedness', 'Make a simple plan for hot days',
                'Keep your essential contacts, local alerts, and useful places together.',
                "Before a hot day, check the weather alerts for your neighborhood and note any planned power outages.\n\nWrite down who you can contact and where you could go if your home becomes uncomfortable. Share the plan with someone you trust.",
                'everyone', 'both', true],
            ['Everyday preparedness', 'Keep your essentials easy to find',
                'Set aside the items you would want close by during an interruption.',
                "Choose an easy-to-reach place for a torch, a charged phone or power bank, important contact details, and the items you use every day.\n\nCheck that everyone in your household knows where these items are. Keep a paper copy of key phone numbers in case your phone is unavailable.",
                'everyone', 'outage', true],
            ['Everyday preparedness', 'Choose quieter activities for hot afternoons',
                'Prepare a few calm indoor activities so your family has options.',
                "Put together a small selection of books, drawing materials, or quiet games before a hot afternoon.\n\nAgree on an alternative plan if a room becomes uncomfortable or the power goes out. Keep your local cooling-point information available.",
                'parents', 'heatwave', true],
            ['Home & equipment', 'Know your equipment before a power cut',
                'Keep instructions and support contacts for the equipment you rely on.',
                "Make a list of the equipment your household relies on. Check the manufacturer's instructions for power interruptions and safe restarting.\n\nIf equipment is essential for someone's care, agree on an interruption plan with the relevant professional or provider before an outage. Keep their contact details accessible.",
                'caregivers', 'outage', true],
            ['Home & equipment', 'Prepare a comfortable room',
                'Notice where your home gets direct sun and plan a place to spend time.',
                "Identify the rooms that receive the most direct sun. Check which blinds or curtains you can use to shade those windows.\n\nChoose a comfortable place for the household to gather and keep pathways clear. If conditions become uncomfortable, check the nearby cooling points and their opening hours.",
                'everyone', 'heatwave', true],
            ['Home & equipment', 'Keep an offline copy of your household plan',
                'Your useful information should still be available without electricity.',
                "Write your key contacts, equipment support numbers, and preferred meeting place on paper.\n\nKeep the plan somewhere everyone can find it. Review it when contact details or household needs change.",
                'everyone', 'both', true],
            ['Family & neighbors', 'Agree on a check-in with someone you trust',
                'Choose a simple way to stay in contact on hot days.',
                "Ask a relative, friend, or neighbor whether you can arrange a regular check-in during hot weather.\n\nAgree on a time and a backup way to make contact if a phone is unavailable. Share only the details you are comfortable sharing.",
                'seniors', 'both', true],
            ['Family & neighbors', 'Build a shared family contact card',
                'Help everyone know who to call and where to meet.',
                "Write down the main household contact numbers and an agreed meeting place. Keep a copy in an accessible location.\n\nTalk through the plan together in simple language. Make sure children know which trusted adult they can approach if they need help.",
                'parents', 'both', true],
            ['Family & neighbors', 'Review a care plan before an interruption',
                'Check contacts and arrangements with the person you support.',
                "Discuss what would be useful during a hot day or power interruption with the person you support.\n\nConfirm the agreed contacts and any equipment arrangements with their care provider. Keep the plan accessible to the people who need it.",
                'caregivers', 'both', true],
            ['Out & about', 'Find a cooling place before you leave',
                'Check the location, accessibility, and opening times before setting off.',
                "Browse HeatAlert's cooling points and choose a place that fits your route. Check its opening hours and accessibility information.\n\nShare your destination with someone if useful, and keep an alternative nearby in mind in case the place is closed or full.",
                'everyone', 'heatwave', true],
            ['Out & about', 'Plan a route with places to pause',
                'Know where you can stop or change your plans during a hot day.',
                "Before leaving, identify shaded stops or public places along your route. Check the local weather alerts and transport information.\n\nAllow room to change your plans. If your usual route is uncomfortable, consider another route or a nearby cooling point.",
                'everyone', 'heatwave', true],
            ['Out & about', 'Neighborhood preparation checklist',
                'A draft checklist for the team to review before publishing.',
                "List the useful places and contacts in your neighborhood.\n\nReview the list with your group and confirm the information before publishing this article.",
                'everyone', 'both', false],
        ];

        foreach ($articles as [$categoryName, $title, $summary, $content, $audience, $situation, $published]) {
            $category = CategorieConseil::where('nom', $categoryName)->firstOrFail();
            $existing = Conseil::where('categorie_conseil_id', $category->id)->where('titre', $title)->first();
            $document = AdviceArticleContent::document($title);
            if (! $existing) {
                Conseil::factory()->create([
                    'categorie_conseil_id' => $category->id, 'titre' => $title, 'resume' => $summary,
                    'contenu' => $content, 'contenu_formate' => $document, 'public_cible' => $audience, 'situation' => $situation, 'actif' => $published,
                ]);
            } elseif ($existing->contenu_formate === null && $existing->contenu === $content) {
                // Upgrade only an untouched original example. Never overwrite an administrator's text.
                $existing->update(['contenu_formate' => $document]);
            }
        }
    }
}
