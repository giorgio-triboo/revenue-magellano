<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Publisher;

class PublisherFactory extends Factory
{
    protected $model = Publisher::class;

    public function definition(): array
    {
        // Lista delle province italiane per esteso
        $provinces = [
            'Agrigento', 'Alessandria', 'Ancona', 'Aosta', 'Arezzo', 'Ascoli Piceno', 'Asti', 'Avellino', 'Bari', 'Barletta-Andria-Trani', 
            'Belluno', 'Benevento', 'Bergamo', 'Biella', 'Bologna', 'Bolzano', 'Brescia', 'Brindisi', 'Cagliari', 'Caltanissetta', 
            'Campobasso', 'Carbonia-Iglesias', 'Caserta', 'Catania', 'Catanzaro', 'Chieti', 'Como', 'Cosenza', 'Cremona', 'Crotone', 
            'Cuneo', 'Enna', 'Fermo', 'Ferrara', 'Firenze', 'Foggia', 'Forlì-Cesena', 'Frosinone', 'Genova', 'Gorizia', 'Grosseto', 
            'Imperia', 'Isernia', 'La Spezia', 'L\'Aquila', 'Latina', 'Lecce', 'Lecco', 'Livorno', 'Lodi', 'Lucca', 'Macerata', 
            'Mantova', 'Massa-Carrara', 'Matera', 'Medio Campidano', 'Messina', 'Milano', 'Modena', 'Monza e Brianza', 'Napoli', 
            'Novara', 'Nuoro', 'Ogliastra', 'Oristano', 'Padova', 'Palermo', 'Parma', 'Pavia', 'Perugia', 'Pesaro e Urbino', 'Pescara', 
            'Piacenza', 'Pisa', 'Pistoia', 'Pordenone', 'Potenza', 'Prato', 'Ragusa', 'Ravenna', 'Reggio Calabria', 'Reggio Emilia', 
            'Rieti', 'Rimini', 'Roma', 'Rovigo', 'Salerno', 'Sassari', 'Savona', 'Siena', 'Siracusa', 'Sondrio', 'Taranto', 'Teramo', 
            'Terni', 'Torino', 'Trapani', 'Trento', 'Treviso', 'Trieste', 'Udine', 'Varese', 'Venezia', 'Verbano-Cusio-Ossola', 
            'Vercelli', 'Verona', 'Vibo Valentia', 'Vicenza', 'Viterbo'
        ];

        $province = $this->faker->randomElement($provinces);

        return [
            'vat_number' => $this->faker->unique()->numerify('###########'), // VAT number simulato
            'company_name' => $this->faker->companySuffix,
            'legal_name' => $this->faker->company,
            'county' => $province,          // Provincia italiana per esteso
            'city' => $province,           // Città uguale alla provincia
            'postal_code' => $this->faker->numerify('#####'), // CAP italiano (5 cifre)
            'iban' => $this->faker->iban('IT'),
            'swift' => $this->faker->swiftBicNumber,
            'website' => $this->faker->url,
            'is_active' => $this->faker->boolean(80),
        ];
    }
}
