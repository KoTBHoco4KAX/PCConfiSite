<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class PcComponentsSeeder extends Seeder
{
    private array $brands = [];
    private array $cats   = [];
    private int   $skuSeq = 0;

    public function run(): void
    {
        $this->seedBrandsAndCategories();

        $this->seedCpus();
        $this->seedGpus();
        $this->seedMotherboards();
        $this->seedRam();
        $this->seedStorage();
        $this->seedPsu();
        $this->seedCases();
        $this->seedCooling();
    }

    private function seedBrandsAndCategories(): void
    {
        $brands = [
            'amd' => 'AMD', 'intel' => 'Intel', 'nvidia' => 'NVIDIA',
            'asus' => 'ASUS', 'msi' => 'MSI', 'gigabyte' => 'Gigabyte',
            'asrock' => 'ASRock', 'corsair' => 'Corsair', 'kingston' => 'Kingston',
            'samsung' => 'Samsung', 'wd' => 'Western Digital', 'seagate' => 'Seagate',
            'crucial' => 'Crucial', 'gskill' => 'G.Skill', 'patriot' => 'Patriot',
            'seasonic' => 'Seasonic', 'bequiet' => 'be quiet!', 'thermaltake' => 'Thermaltake',
            'noctua' => 'Noctua', 'nzxt' => 'NZXT', 'lianli' => 'Lian Li',
            'fractal' => 'Fractal Design', 'arctic' => 'Arctic', 'deepcool' => 'DeepCool',
            'sapphire' => 'Sapphire', 'xfx' => 'XFX', 'powercolor' => 'PowerColor',
            'palit' => 'Palit', 'evga' => 'EVGA', 'zotac' => 'Zotac',
            'sabrent' => 'Sabrent', 'adata' => 'ADATA', 'teamgroup' => 'TeamGroup',
            'coolermaster' => 'Cooler Master', 'silverstone' => 'Silverstone',
            'idcooling' => 'ID-Cooling', 'gamemax' => 'GameMax',
        ];

        foreach ($brands as $slug => $name) {
            $existing = Brand::where('name', $name)->orWhere('slug', $slug)->first();
            $this->brands[$slug] = $existing ?: Brand::create(['slug' => $slug, 'name' => $name]);
        }

        $cats = [
            'cpu' => 'Процессоры',
            'gpu' => 'Видеокарты',
            'motherboard' => 'Материнские платы',
            'ram' => 'Оперативная память',
            'storage' => 'Накопители',
            'pc_case' => 'Корпуса',
            'cooling' => 'Охлаждение',
            'psu' => 'Блоки питания',
        ];

        foreach ($cats as $slug => $name) {
            $existing = Category::where('name', $name)->orWhere('slug', $slug)->first();
            $this->cats[$slug] = $existing ?: Category::create(['slug' => $slug, 'name' => $name]);
        }
    }

    private function wipe(string $catSlug): void
    {
        $id = $this->cats[$catSlug]->id;
        Product::where('category_id', $id)->get()->each->delete();
    }

    private function makeSku(string $catSlug, string $name): string
    {
        $this->skuSeq++;
        $prefix = strtoupper(substr($catSlug, 0, 3));
        $slug   = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '-', $name));
        $slug   = trim(substr($slug, 0, 40), '-');
        return sprintf('%s-%s-%04d', $prefix, $slug, $this->skuSeq);
    }

    private function make(
        string $catSlug,
        string $brandSlug,
        string $name,
        int $price,
        string $specRelation,
        array $spec
    ): void {
        $p = Product::create([
            'category_id' => $this->cats[$catSlug]->id,
            'brand_id'    => $this->brands[$brandSlug]->id,
            'name'        => $name,
            'sku'         => $this->makeSku($catSlug, $name),
            'price'       => $price,
            'stock'       => 100,
            'is_active'   => true,
        ]);
        $p->{$specRelation}()->create($spec);
    }

    // ============================================================
    // CPUs — spec: socket, cores, performance, tdp, has_igpu
    // ============================================================
    private function seedCpus(): void
    {
        $this->wipe('cpu');

        $data = [
            ['Intel Core i3-12100F', 'intel', 8500,  'LGA1700', 58,  false, 5, 4],
            ['Intel Core i3-12100',  'intel', 11000, 'LGA1700', 60,  true,  5, 4],
            ['Intel Core i5-12400F', 'intel', 13500, 'LGA1700', 65,  false, 6, 6],
            ['Intel Core i5-12400',  'intel', 16000, 'LGA1700', 65,  true,  6, 6],
            ['Intel Core i5-12600K', 'intel', 21000, 'LGA1700', 125, true,  7, 10],
            ['Intel Core i5-12600KF','intel', 19500, 'LGA1700', 125, false, 7, 10],
            ['Intel Core i5-13400F', 'intel', 17500, 'LGA1700', 65,  false, 7, 10],
            ['Intel Core i5-13600K', 'intel', 27500, 'LGA1700', 125, true,  8, 14],
            ['Intel Core i5-13600KF','intel', 26000, 'LGA1700', 125, false, 8, 14],
            ['Intel Core i5-14400F', 'intel', 19500, 'LGA1700', 65,  false, 7, 10],
            ['Intel Core i5-14600K', 'intel', 32000, 'LGA1700', 125, true,  8, 14],
            ['Intel Core i5-14600KF','intel', 30000, 'LGA1700', 125, false, 8, 14],
            ['Intel Core i7-12700K', 'intel', 31000, 'LGA1700', 125, true,  8, 12],
            ['Intel Core i7-12700KF','intel', 29000, 'LGA1700', 125, false, 8, 12],
            ['Intel Core i7-13700K', 'intel', 42000, 'LGA1700', 125, true,  9, 16],
            ['Intel Core i7-13700KF','intel', 40000, 'LGA1700', 125, false, 9, 16],
            ['Intel Core i7-14700K', 'intel', 48000, 'LGA1700', 125, true,  9, 20],
            ['Intel Core i7-14700KF','intel', 45000, 'LGA1700', 125, false, 9, 20],
            ['Intel Core i9-12900K', 'intel', 45000, 'LGA1700', 125, true,  9, 16],
            ['Intel Core i9-12900KF','intel', 42000, 'LGA1700', 125, false, 9, 16],
            ['Intel Core i9-13900K', 'intel', 62000, 'LGA1700', 125, true,  10, 24],
            ['Intel Core i9-13900KF','intel', 58000, 'LGA1700', 125, false, 10, 24],
            ['Intel Core i9-13900KS','intel', 72000, 'LGA1700', 150, true,  10, 24],
            ['Intel Core i9-14900K', 'intel', 68000, 'LGA1700', 125, true,  10, 24],
            ['Intel Core i9-14900KF','intel', 64000, 'LGA1700', 125, false, 10, 24],
            ['Intel Core i9-14900KS','intel', 80000, 'LGA1700', 150, true,  10, 24],

            ['Intel Core Ultra 5 245K',  'intel', 40000, 'LGA1851', 125, true,  8, 14],
            ['Intel Core Ultra 5 245KF', 'intel', 38000, 'LGA1851', 125, false, 8, 14],
            ['Intel Core Ultra 7 265K',  'intel', 55000, 'LGA1851', 125, true,  9, 20],
            ['Intel Core Ultra 7 265KF', 'intel', 52000, 'LGA1851', 125, false, 9, 20],
            ['Intel Core Ultra 9 285K',  'intel', 78000, 'LGA1851', 125, true,  10, 24],
            ['Intel Core Ultra 9 285KF', 'intel', 74000, 'LGA1851', 125, false, 10, 24],

            ['Intel Core i5-10400F', 'intel', 9500,  'LGA1200', 65,  false, 5, 6],
            ['Intel Core i5-11400F', 'intel', 12000, 'LGA1200', 65,  false, 6, 6],
            ['Intel Core i7-10700K', 'intel', 22000, 'LGA1200', 125, true,  7, 8],
            ['Intel Core i7-11700K', 'intel', 24000, 'LGA1200', 125, true,  7, 8],
            ['Intel Core i9-10900K', 'intel', 32000, 'LGA1200', 125, true,  8, 10],
            ['Intel Core i9-11900K', 'intel', 34000, 'LGA1200', 125, true,  8, 8],

            ['Intel Core i3-8100',  'intel', 5500, 'LGA1151', 65,  true,  4, 4],
            ['Intel Core i5-8400',  'intel', 9000, 'LGA1151', 65,  true,  5, 6],
            ['Intel Core i5-9400F', 'intel', 8000, 'LGA1151', 65,  false, 5, 6],
            ['Intel Core i5-9600K', 'intel', 11000,'LGA1151', 95,  true,  6, 6],
            ['Intel Core i7-8700K', 'intel', 16000,'LGA1151', 95,  true,  6, 6],
            ['Intel Core i7-9700K', 'intel', 18000,'LGA1151', 95,  true,  7, 8],
            ['Intel Core i9-9900K', 'intel', 26000,'LGA1151', 95,  true,  7, 8],

            ['Intel Core i5-6600K', 'intel', 7000, 'LGA1151', 91, true,  5, 4],
            ['Intel Core i7-6700K', 'intel', 11000,'LGA1151', 91, true,  6, 4],
            ['Intel Core i7-7700K', 'intel', 13000,'LGA1151', 91, true,  6, 4],

            ['Intel Core i5-4460',  'intel', 4500, 'LGA1150', 84, true,  4, 4],
            ['Intel Core i5-4690K', 'intel', 6000, 'LGA1150', 88, true,  5, 4],
            ['Intel Core i7-4790K', 'intel', 9500, 'LGA1150', 88, true,  6, 4],

            ['Intel Core i5-3570K', 'intel', 3500, 'LGA1155', 77, true,  4, 4],
            ['Intel Core i7-3770K', 'intel', 5500, 'LGA1155', 77, true,  5, 4],

            ['AMD Ryzen 5 7500F',    'amd', 15000, 'AM5', 65,  false, 6, 6],
            ['AMD Ryzen 5 7600',     'amd', 18000, 'AM5', 65,  true,  7, 6],
            ['AMD Ryzen 5 7600X',    'amd', 19500, 'AM5', 105, true,  7, 6],
            ['AMD Ryzen 5 8400F',    'amd', 16500, 'AM5', 65,  false, 6, 6],
            ['AMD Ryzen 5 9600X',    'amd', 25000, 'AM5', 65,  true,  8, 6],
            ['AMD Ryzen 7 7700',     'amd', 26000, 'AM5', 65,  true,  8, 8],
            ['AMD Ryzen 7 7700X',    'amd', 28000, 'AM5', 105, true,  8, 8],
            ['AMD Ryzen 7 7800X3D',  'amd', 40000, 'AM5', 120, true,  9, 8],
            ['AMD Ryzen 7 9700X',    'amd', 38000, 'AM5', 65,  true,  9, 8],
            ['AMD Ryzen 7 9800X3D',  'amd', 55000, 'AM5', 120, true,  10, 8],
            ['AMD Ryzen 9 7900',     'amd', 40000, 'AM5', 65,  true,  9, 12],
            ['AMD Ryzen 9 7900X',    'amd', 45000, 'AM5', 170, true,  9, 12],
            ['AMD Ryzen 9 7950X',    'amd', 60000, 'AM5', 170, true,  10, 16],
            ['AMD Ryzen 9 7950X3D',  'amd', 75000, 'AM5', 120, true,  10, 16],
            ['AMD Ryzen 9 9900X',    'amd', 58000, 'AM5', 120, true,  10, 12],
            ['AMD Ryzen 9 9950X',    'amd', 78000, 'AM5', 170, true,  10, 16],
            ['AMD Ryzen 9 9950X3D',  'amd', 95000, 'AM5', 170, true,  10, 16],

            ['AMD Ryzen 3 1200',    'amd', 3500, 'AM4', 65, false, 4, 4],
            ['AMD Ryzen 3 3100',    'amd', 5000, 'AM4', 65, false, 5, 4],
            ['AMD Ryzen 5 1600',    'amd', 6000, 'AM4', 65, false, 5, 6],
            ['AMD Ryzen 5 2600',    'amd', 7500, 'AM4', 65, false, 5, 6],
            ['AMD Ryzen 5 3600',    'amd', 9500, 'AM4', 65, false, 6, 6],
            ['AMD Ryzen 5 3600X',   'amd', 11000,'AM4', 95, false, 6, 6],
            ['AMD Ryzen 5 5600',    'amd', 12000,'AM4', 65, false, 7, 6],
            ['AMD Ryzen 5 5600X',   'amd', 14000,'AM4', 65, false, 7, 6],
            ['AMD Ryzen 5 5600X3D', 'amd', 18000,'AM4', 105,false, 8, 6],
            ['AMD Ryzen 7 1700',    'amd', 8500, 'AM4', 65, false, 6, 8],
            ['AMD Ryzen 7 2700',    'amd', 10000,'AM4', 65, false, 6, 8],
            ['AMD Ryzen 7 3700X',   'amd', 14000,'AM4', 65, false, 7, 8],
            ['AMD Ryzen 7 3800X',   'amd', 16000,'AM4', 105,false, 7, 8],
            ['AMD Ryzen 7 5700X',   'amd', 17000,'AM4', 65, false, 8, 8],
            ['AMD Ryzen 7 5700X3D', 'amd', 22000,'AM4', 105,false, 8, 8],
            ['AMD Ryzen 7 5800X',   'amd', 20000,'AM4', 105,false, 8, 8],
            ['AMD Ryzen 7 5800X3D', 'amd', 28000,'AM4', 105,false, 9, 8],
            ['AMD Ryzen 9 3900X',   'amd', 24000,'AM4', 105,false, 8, 12],
            ['AMD Ryzen 9 3950X',   'amd', 38000,'AM4', 105,false, 9, 16],
            ['AMD Ryzen 9 5900X',   'amd', 30000,'AM4', 105,false, 9, 12],
            ['AMD Ryzen 9 5950X',   'amd', 42000,'AM4', 105,false, 10, 16],

            ['AMD FX-6300', 'amd', 2500, 'AM3+', 95,  false, 3, 6],
            ['AMD FX-8350', 'amd', 3500, 'AM3+', 125, false, 4, 8],
        ];

        foreach ($data as [$name, $brand, $price, $socket, $tdp, $igpu, $perf, $cores]) {
            $this->make('cpu', $brand, $name, $price, 'cpuSpec', [
                'socket'      => $socket,
                'cores'       => $cores,
                'performance' => $perf,
                'tdp'         => $tdp,
                'has_igpu'    => $igpu,
            ]);
        }
    }

    // ============================================================
    // GPUs — spec: vram, performance, tdp
    // ============================================================
    private function seedGpus(): void
    {
        $this->wipe('gpu');

        $data = [
            ['NVIDIA GeForce RTX 5090',    'nvidia', 320000, 575, 10, 32],
            ['NVIDIA GeForce RTX 5080',    'nvidia', 180000, 360, 10, 16],
            ['NVIDIA GeForce RTX 5070 Ti', 'nvidia', 130000, 300, 9, 16],
            ['NVIDIA GeForce RTX 5070',    'nvidia', 95000,  250, 9, 12],
            ['NVIDIA GeForce RTX 5060 Ti', 'nvidia', 60000,  180, 8, 16],
            ['NVIDIA GeForce RTX 5060',    'nvidia', 45000,  150, 7, 8],

            ['NVIDIA GeForce RTX 4090',    'nvidia', 230000, 450, 10, 24],
            ['NVIDIA GeForce RTX 4080 Super','nvidia',145000, 320, 10, 16],
            ['NVIDIA GeForce RTX 4080',    'nvidia', 130000, 320, 9, 16],
            ['NVIDIA GeForce RTX 4070 Ti Super','nvidia',95000, 285, 9, 16],
            ['NVIDIA GeForce RTX 4070 Ti', 'nvidia', 85000,  285, 9, 12],
            ['NVIDIA GeForce RTX 4070 Super','nvidia',70000, 220, 8, 12],
            ['NVIDIA GeForce RTX 4070',    'nvidia', 60000,  200, 8, 12],
            ['NVIDIA GeForce RTX 4060 Ti', 'nvidia', 42000,  160, 7, 8],
            ['NVIDIA GeForce RTX 4060',    'nvidia', 32000,  115, 6, 8],

            ['NVIDIA GeForce RTX 3090 Ti', 'nvidia', 180000, 450, 9, 24],
            ['NVIDIA GeForce RTX 3090',    'nvidia', 140000, 350, 9, 24],
            ['NVIDIA GeForce RTX 3080 Ti', 'nvidia', 110000, 350, 9, 12],
            ['NVIDIA GeForce RTX 3080',    'nvidia', 80000,  320, 8, 10],
            ['NVIDIA GeForce RTX 3070 Ti', 'nvidia', 60000,  290, 8, 8],
            ['NVIDIA GeForce RTX 3070',    'nvidia', 48000,  220, 7, 8],
            ['NVIDIA GeForce RTX 3060 Ti', 'nvidia', 38000,  200, 7, 8],
            ['NVIDIA GeForce RTX 3060',    'nvidia', 28000,  170, 6, 12],
            ['NVIDIA GeForce RTX 3050',    'nvidia', 20000,  130, 5, 8],

            ['NVIDIA GeForce RTX 2080 Ti', 'nvidia', 70000, 250, 7, 11],
            ['NVIDIA GeForce RTX 2080 Super','nvidia',55000, 250, 7, 8],
            ['NVIDIA GeForce RTX 2080',    'nvidia', 45000, 215, 7, 8],
            ['NVIDIA GeForce RTX 2070 Super','nvidia',40000, 215, 6, 8],
            ['NVIDIA GeForce RTX 2070',    'nvidia', 35000, 175, 6, 8],
            ['NVIDIA GeForce RTX 2060 Super','nvidia',32000, 175, 6, 8],
            ['NVIDIA GeForce RTX 2060',    'nvidia', 24000, 160, 5, 6],

            ['NVIDIA GeForce GTX 1660 Ti', 'nvidia', 22000, 120, 5, 6],
            ['NVIDIA GeForce GTX 1660 Super','nvidia',20000, 125, 5, 6],
            ['NVIDIA GeForce GTX 1660',    'nvidia', 18000, 120, 5, 6],
            ['NVIDIA GeForce GTX 1650 Super','nvidia',15000, 100, 4, 4],
            ['NVIDIA GeForce GTX 1650',    'nvidia', 12000, 75,  4, 4],

            ['NVIDIA GeForce GTX 1080 Ti', 'nvidia', 40000, 250, 6, 11],
            ['NVIDIA GeForce GTX 1080',    'nvidia', 30000, 180, 6, 8],
            ['NVIDIA GeForce GTX 1070 Ti', 'nvidia', 24000, 180, 5, 8],
            ['NVIDIA GeForce GTX 1070',    'nvidia', 20000, 150, 5, 8],
            ['NVIDIA GeForce GTX 1060 6GB','nvidia', 14000, 120, 4, 6],
            ['NVIDIA GeForce GTX 1060 3GB','nvidia', 10000, 120, 4, 3],
            ['NVIDIA GeForce GTX 1050 Ti', 'nvidia', 8000,  75,  3, 4],
            ['NVIDIA GeForce GTX 1050',    'nvidia', 6000,  75,  3, 2],

            ['NVIDIA GeForce GTX 980 Ti',  'nvidia', 22000, 250, 5, 6],
            ['NVIDIA GeForce GTX 980',     'nvidia', 15000, 165, 5, 4],
            ['NVIDIA GeForce GTX 970',     'nvidia', 10000, 145, 4, 4],
            ['NVIDIA GeForce GTX 960',     'nvidia', 6500,  120, 3, 2],
            ['NVIDIA GeForce GTX 950',     'nvidia', 5000,  90,  3, 2],

            ['NVIDIA GeForce GTX 780 Ti',  'nvidia', 12000, 250, 4, 3],
            ['NVIDIA GeForce GTX 780',     'nvidia', 8000,  250, 4, 3],
            ['NVIDIA GeForce GTX 770',     'nvidia', 5500,  230, 3, 2],
            ['NVIDIA GeForce GTX 760',     'nvidia', 4000,  170, 3, 2],
            ['NVIDIA GeForce GTX 750 Ti',  'nvidia', 3000,  60,  2, 2],

            ['NVIDIA GeForce GTX 680',     'nvidia', 6000,  195, 3, 2],
            ['NVIDIA GeForce GTX 670',     'nvidia', 4500,  170, 3, 2],
            ['NVIDIA GeForce GTX 660 Ti',  'nvidia', 3500,  150, 2, 2],
            ['NVIDIA GeForce GTX 660',     'nvidia', 2500,  140, 2, 2],

            ['NVIDIA GeForce GTX 580',     'nvidia', 4000, 244, 3, 1],
            ['NVIDIA GeForce GTX 570',     'nvidia', 3000, 219, 2, 1],
            ['NVIDIA GeForce GTX 560 Ti',  'nvidia', 2000, 170, 2, 1],

            ['AMD Radeon RX 9070 XT',      'amd', 85000, 304, 9, 16],
            ['AMD Radeon RX 9070',         'amd', 70000, 220, 8, 16],
            ['AMD Radeon RX 9060 XT',      'amd', 42000, 160, 7, 16],

            ['AMD Radeon RX 7900 XTX',     'amd', 110000, 355, 9, 24],
            ['AMD Radeon RX 7900 XT',      'amd', 85000,  315, 9, 20],
            ['AMD Radeon RX 7900 GRE',     'amd', 65000,  260, 8, 16],
            ['AMD Radeon RX 7800 XT',      'amd', 52000,  263, 8, 16],
            ['AMD Radeon RX 7700 XT',      'amd', 42000,  245, 7, 12],
            ['AMD Radeon RX 7600',         'amd', 28000,  165, 6, 8],

            ['AMD Radeon RX 6950 XT',      'amd', 80000, 335, 8, 16],
            ['AMD Radeon RX 6900 XT',      'amd', 65000, 300, 8, 16],
            ['AMD Radeon RX 6800 XT',      'amd', 50000, 300, 8, 16],
            ['AMD Radeon RX 6800',         'amd', 42000, 250, 7, 16],
            ['AMD Radeon RX 6750 XT',      'amd', 35000, 250, 7, 12],
            ['AMD Radeon RX 6700 XT',      'amd', 30000, 230, 6, 12],
            ['AMD Radeon RX 6650 XT',      'amd', 26000, 180, 6, 8],
            ['AMD Radeon RX 6600 XT',      'amd', 22000, 160, 6, 8],
            ['AMD Radeon RX 6600',         'amd', 18000, 132, 5, 8],
            ['AMD Radeon RX 6500 XT',      'amd', 12000, 107, 4, 4],
            ['AMD Radeon RX 6400',         'amd', 9000,  53,  3, 4],

            ['AMD Radeon RX 5700 XT',      'amd', 20000, 225, 5, 8],
            ['AMD Radeon RX 5700',         'amd', 17000, 180, 5, 8],
            ['AMD Radeon RX 5600 XT',      'amd', 13000, 150, 5, 6],
            ['AMD Radeon RX 5500 XT',      'amd', 9000,  130, 4, 8],

            ['AMD Radeon RX Vega 64',      'amd', 18000, 295, 5, 8],
            ['AMD Radeon RX Vega 56',      'amd', 14000, 210, 4, 8],

            ['AMD Radeon RX 590',          'amd', 9000, 175, 4, 8],
            ['AMD Radeon RX 580 8GB',      'amd', 8000, 185, 4, 8],
            ['AMD Radeon RX 570 4GB',      'amd', 6000, 150, 3, 4],
            ['AMD Radeon RX 560',          'amd', 4000, 80,  3, 4],
            ['AMD Radeon RX 550',          'amd', 3000, 50,  2, 2],
            ['AMD Radeon RX 480 8GB',      'amd', 6500, 150, 4, 8],
            ['AMD Radeon RX 470',          'amd', 5000, 120, 3, 4],

            ['AMD Radeon R9 390X',         'amd', 7000, 275, 4, 8],
            ['AMD Radeon R9 390',          'amd', 5500, 275, 4, 8],
            ['AMD Radeon R9 380',          'amd', 4000, 190, 3, 4],
            ['AMD Radeon R9 290X',         'amd', 4500, 290, 4, 4],
            ['AMD Radeon R7 370',          'amd', 3000, 110, 2, 4],

            ['AMD Radeon HD 7970',         'amd', 3500, 250, 3, 3],
            ['AMD Radeon HD 7950',         'amd', 2800, 200, 3, 3],
        ];

        foreach ($data as [$name, $brand, $price, $tdp, $perf, $vram]) {
            $this->make('gpu', $brand, $name, $price, 'gpuSpec', [
                'vram'        => $vram,
                'performance' => $perf,
                'tdp'         => $tdp,
            ]);
        }
    }

    // ============================================================
    // Motherboards — spec: socket, chipset, form_factor, ram_type
    // ============================================================
    private function seedMotherboards(): void
    {
        $this->wipe('motherboard');

        $configs = [
            ['AM5', 'A620', 'DDR5', 'entry', 8000, 11000],
            ['AM5', 'B650', 'DDR5', 'mid',   14000, 22000],
            ['AM5', 'B650E','DDR5', 'mid',   20000, 28000],
            ['AM5', 'X670', 'DDR5', 'high',  30000, 42000],
            ['AM5', 'X670E','DDR5', 'high',  38000, 55000],
            ['AM5', 'B840', 'DDR5', 'mid',   12000, 17000],
            ['AM5', 'B850', 'DDR5', 'mid',   16000, 25000],
            ['AM5', 'X870', 'DDR5', 'high',  32000, 45000],
            ['AM5', 'X870E','DDR5', 'high',  40000, 60000],

            ['AM4', 'A320', 'DDR4', 'entry', 3500, 5000],
            ['AM4', 'B350', 'DDR4', 'entry', 4500, 6500],
            ['AM4', 'X370', 'DDR4', 'mid',   6000, 9000],
            ['AM4', 'B450', 'DDR4', 'entry', 5500, 8500],
            ['AM4', 'X470', 'DDR4', 'mid',   8000, 13000],
            ['AM4', 'A520', 'DDR4', 'entry', 4000, 6000],
            ['AM4', 'B550', 'DDR4', 'mid',   8500, 15000],
            ['AM4', 'X570', 'DDR4', 'high',  14000, 24000],

            ['LGA1700', 'H610', 'DDR4', 'entry', 6000, 8500],
            ['LGA1700', 'H610', 'DDR5', 'entry', 7500, 10500],
            ['LGA1700', 'B660', 'DDR4', 'mid',   10000, 15000],
            ['LGA1700', 'B660', 'DDR5', 'mid',   12000, 18000],
            ['LGA1700', 'H670', 'DDR4', 'mid',   14000, 19000],
            ['LGA1700', 'Z690', 'DDR4', 'high',  18000, 28000],
            ['LGA1700', 'Z690', 'DDR5', 'high',  22000, 35000],
            ['LGA1700', 'B760', 'DDR4', 'mid',   11000, 16000],
            ['LGA1700', 'B760', 'DDR5', 'mid',   13000, 20000],
            ['LGA1700', 'H770', 'DDR5', 'mid',   16000, 22000],
            ['LGA1700', 'Z790', 'DDR4', 'high',  22000, 32000],
            ['LGA1700', 'Z790', 'DDR5', 'high',  26000, 42000],

            ['LGA1851', 'B860', 'DDR5', 'mid',   18000, 26000],
            ['LGA1851', 'Z890', 'DDR5', 'high',  32000, 55000],

            ['LGA1200', 'H410', 'DDR4', 'entry', 4500, 6500],
            ['LGA1200', 'B460', 'DDR4', 'mid',   6500, 9500],
            ['LGA1200', 'H470', 'DDR4', 'mid',   8000, 11000],
            ['LGA1200', 'Z490', 'DDR4', 'high',  12000, 20000],
            ['LGA1200', 'H510', 'DDR4', 'entry', 5000, 7000],
            ['LGA1200', 'B560', 'DDR4', 'mid',   8000, 12000],
            ['LGA1200', 'H570', 'DDR4', 'mid',   10000, 14000],
            ['LGA1200', 'Z590', 'DDR4', 'high',  14000, 24000],

            ['LGA1151', 'H110', 'DDR4', 'entry', 3000, 4500],
            ['LGA1151', 'B250', 'DDR4', 'entry', 4000, 6000],
            ['LGA1151', 'Z270', 'DDR4', 'mid',   7000, 12000],
            ['LGA1151', 'B360', 'DDR4', 'entry', 4500, 7000],
            ['LGA1151', 'H370', 'DDR4', 'mid',   6000, 9000],
            ['LGA1151', 'Z370', 'DDR4', 'mid',   9000, 14000],
            ['LGA1151', 'B365', 'DDR4', 'entry', 5000, 7500],
            ['LGA1151', 'Z390', 'DDR4', 'high',  12000, 20000],

            ['LGA1150', 'H81', 'DDR3', 'entry', 2000, 3500],
            ['LGA1150', 'B85', 'DDR3', 'entry', 3000, 4500],
            ['LGA1150', 'H87', 'DDR3', 'mid',   3500, 5500],
            ['LGA1150', 'Z87', 'DDR3', 'mid',   5000, 8000],
            ['LGA1150', 'H97', 'DDR3', 'mid',   4000, 6000],
            ['LGA1150', 'Z97', 'DDR3', 'high',  6500, 10000],

            ['LGA1155', 'H61', 'DDR3', 'entry', 1500, 2500],
            ['LGA1155', 'B75', 'DDR3', 'entry', 2000, 3500],
            ['LGA1155', 'H77', 'DDR3', 'mid',   3000, 4500],
            ['LGA1155', 'Z77', 'DDR3', 'mid',   4500, 7500],

            ['AM3+', '760G', 'DDR3', 'entry', 2500, 4000],
            ['AM3+', '970',  'DDR3', 'mid',   3500, 5500],
            ['AM3+', '990FX','DDR3', 'high',  5000, 8000],
        ];

        $moboBrands = ['asus', 'msi', 'gigabyte', 'asrock'];
        $moboNames = [
            'entry' => ['PRIME', 'PRO', 'DS3H', 'Pro4'],
            'mid'   => ['TUF Gaming', 'MAG', 'AORUS Elite', 'Steel Legend'],
            'high'  => ['ROG Strix', 'MPG', 'AORUS Master', 'Taichi'],
        ];

        foreach ($configs as [$socket, $chipset, $ramType, $tier, $pMin, $pMax]) {
            $forms = match ($tier) {
                'entry' => [['ATX', $pMin], ['mATX', $pMin - 1000]],
                'mid'   => [['ATX', $pMax], ['mATX', $pMax - 2000]],
                'high'  => [['ATX', $pMax], ['ITX', $pMax - 3000]],
            };

            foreach ($forms as $i => [$form, $price]) {
                $brandSlug = $moboBrands[($i + crc32($chipset)) % count($moboBrands)];
                $lineName  = $moboNames[$tier][($i + crc32($chipset)) % count($moboNames[$tier])];

                $this->make(
                    'motherboard',
                    $brandSlug,
                    sprintf('%s %s %s', strtoupper($brandSlug), $lineName, $chipset),
                    max(1500, $price),
                    'motherboardSpec',
                    [
                        'socket'      => $socket,
                        'chipset'     => $chipset,
                        'form_factor' => $form,
                        'ram_type'    => $ramType,
                    ]
                );
            }
        }
    }

    // ============================================================
    // RAM — spec: capacity, type, speed
    // ============================================================
    private function seedRam(): void
    {
        $this->wipe('ram');

        $data = [
            ['DDR3', 4,  1333, 1200],
            ['DDR3', 8,  1600, 2000],
            ['DDR3', 16, 1600, 3800],
            ['DDR3', 16, 1866, 4500],
            ['DDR3', 32, 1600, 7500],

            ['DDR4', 8,  2666, 1800],
            ['DDR4', 8,  3200, 2200],
            ['DDR4', 16, 3000, 3500],
            ['DDR4', 16, 3200, 3800],
            ['DDR4', 16, 3600, 4500],
            ['DDR4', 32, 3200, 7000],
            ['DDR4', 32, 3600, 8500],
            ['DDR4', 64, 3200, 14000],
            ['DDR4', 64, 3600, 16500],
            ['DDR4', 128, 3200, 30000],

            ['DDR5', 16, 4800, 5500],
            ['DDR5', 16, 5600, 6500],
            ['DDR5', 32, 5200, 9500],
            ['DDR5', 32, 5600, 11000],
            ['DDR5', 32, 6000, 13000],
            ['DDR5', 32, 6400, 15500],
            ['DDR5', 48, 6000, 20000],
            ['DDR5', 64, 5600, 22000],
            ['DDR5', 64, 6000, 26000],
            ['DDR5', 96, 6000, 38000],
            ['DDR5', 128, 5600, 50000],
        ];

        $ramBrands = ['kingston', 'corsair', 'gskill', 'crucial', 'patriot', 'teamgroup', 'adata'];

        foreach ($data as $i => [$type, $capacity, $speed, $price]) {
            $brand = $ramBrands[$i % count($ramBrands)];
            $this->make(
                'ram',
                $brand,
                sprintf('%s %dGB %s %d MHz', strtoupper($brand), $capacity, $type, $speed),
                $price,
                'ramSpec',
                ['capacity' => $capacity, 'type' => $type, 'speed' => $speed]
            );
        }
    }

    // ============================================================
    // Storage — spec: type, capacity
    // ============================================================
    private function seedStorage(): void
    {
        $this->wipe('storage');

        $data = [
            ['Seagate Barracuda 500GB HDD',  'seagate', 2200,  'HDD', 500],
            ['Seagate Barracuda 1TB HDD',    'seagate', 3200,  'HDD', 1000],
            ['Seagate Barracuda 2TB HDD',    'seagate', 4800,  'HDD', 2000],
            ['Seagate Barracuda 4TB HDD',    'seagate', 7500,  'HDD', 4000],
            ['Seagate IronWolf 8TB HDD',     'seagate', 16000, 'HDD', 8000],
            ['WD Blue 1TB HDD',              'wd',      3300,  'HDD', 1000],
            ['WD Blue 2TB HDD',              'wd',      5000,  'HDD', 2000],
            ['WD Black 4TB HDD',             'wd',      9500,  'HDD', 4000],
            ['WD Red Plus 8TB HDD',          'wd',      18000, 'HDD', 8000],
            ['Toshiba P300 3TB HDD',         'wd',      6000,  'HDD', 3000],

            ['Samsung 870 EVO 500GB SATA',   'samsung', 4500,  'SATA SSD', 500],
            ['Samsung 870 EVO 1TB SATA',     'samsung', 7000,  'SATA SSD', 1000],
            ['Samsung 870 EVO 2TB SATA',     'samsung', 13000, 'SATA SSD', 2000],
            ['Samsung 870 EVO 4TB SATA',     'samsung', 26000, 'SATA SSD', 4000],
            ['Crucial MX500 500GB SATA',     'crucial', 3800,  'SATA SSD', 500],
            ['Crucial MX500 1TB SATA',       'crucial', 6200,  'SATA SSD', 1000],
            ['Crucial MX500 2TB SATA',       'crucial', 11500, 'SATA SSD', 2000],
            ['Kingston KC600 512GB SATA',    'kingston',4000,  'SATA SSD', 512],
            ['Kingston KC600 1TB SATA',      'kingston',6800,  'SATA SSD', 1024],
            ['WD Blue SA510 500GB SATA',     'wd',      3600,  'SATA SSD', 500],
            ['WD Blue SA510 1TB SATA',       'wd',      6500,  'SATA SSD', 1000],

            ['Samsung 970 EVO Plus 500GB NVMe',  'samsung', 5000,  'NVMe SSD', 500],
            ['Samsung 970 EVO Plus 1TB NVMe',    'samsung', 8000,  'NVMe SSD', 1024],
            ['Samsung 970 EVO Plus 2TB NVMe',    'samsung', 15000, 'NVMe SSD', 2048],
            ['Samsung 980 PRO 1TB NVMe',         'samsung', 10500, 'NVMe SSD', 1024],
            ['Samsung 980 PRO 2TB NVMe',         'samsung', 18000, 'NVMe SSD', 2048],
            ['Samsung 990 PRO 1TB NVMe',         'samsung', 12500, 'NVMe SSD', 1024],
            ['Samsung 990 PRO 2TB NVMe',         'samsung', 22000, 'NVMe SSD', 2048],
            ['Samsung 990 PRO 4TB NVMe',         'samsung', 45000, 'NVMe SSD', 4096],
            ['WD Black SN770 500GB NVMe',        'wd',      4500,  'NVMe SSD', 500],
            ['WD Black SN770 1TB NVMe',          'wd',      7500,  'NVMe SSD', 1024],
            ['WD Black SN770 2TB NVMe',          'wd',      14000, 'NVMe SSD', 2048],
            ['WD Black SN850X 1TB NVMe',         'wd',      10500, 'NVMe SSD', 1024],
            ['WD Black SN850X 2TB NVMe',         'wd',      19000, 'NVMe SSD', 2048],
            ['WD Black SN850X 4TB NVMe',         'wd',      38000, 'NVMe SSD', 4096],
            ['Crucial P3 Plus 500GB NVMe',       'crucial', 4000,  'NVMe SSD', 500],
            ['Crucial P3 Plus 1TB NVMe',         'crucial', 6800,  'NVMe SSD', 1024],
            ['Crucial P3 Plus 2TB NVMe',         'crucial', 12000, 'NVMe SSD', 2048],
            ['Crucial T700 1TB PCIe 5.0 NVMe',   'crucial', 18000, 'NVMe SSD', 1024],
            ['Crucial T700 2TB PCIe 5.0 NVMe',   'crucial', 32000, 'NVMe SSD', 2048],
            ['Kingston KC3000 1TB NVMe',         'kingston',9500,  'NVMe SSD', 1024],
            ['Kingston KC3000 2TB NVMe',         'kingston',17000, 'NVMe SSD', 2048],
            ['Kingston Fury Renegade 2TB NVMe',  'kingston',19500, 'NVMe SSD', 2048],
            ['Sabrent Rocket 4 Plus 2TB NVMe',   'sabrent', 20000, 'NVMe SSD', 2048],
            ['Sabrent Rocket 5 4TB PCIe 5.0',    'sabrent', 55000, 'NVMe SSD', 4096],
            ['ADATA XPG Gammix S70 1TB NVMe',    'adata',   8500,  'NVMe SSD', 1024],
            ['ADATA XPG Gammix S70 2TB NVMe',    'adata',   15000, 'NVMe SSD', 2048],
        ];

        foreach ($data as [$name, $brand, $price, $type, $capacity]) {
            $this->make('storage', $brand, $name, $price, 'storageSpec', [
                'type'     => $type,
                'capacity' => $capacity,
            ]);
        }
    }

    // ============================================================
    // PSU — spec: power
    // ============================================================
    private function seedPsu(): void
    {
        $this->wipe('psu');

        $data = [
            ['Corsair CX450',                  'corsair',     4500,  450],
            ['Corsair CX550',                  'corsair',     5500,  550],
            ['Corsair CX650',                  'corsair',     6500,  650],
            ['Corsair RM650',                  'corsair',     10500, 650],
            ['Corsair RM750',                  'corsair',     12500, 750],
            ['Corsair RM850',                  'corsair',     15000, 850],
            ['Corsair RM1000',                 'corsair',     20000, 1000],
            ['Corsair HX1200',                 'corsair',     32000, 1200],
            ['Corsair HX1500i',                'corsair',     48000, 1500],
            ['Seasonic Focus GX-550',          'seasonic',    8500,  550],
            ['Seasonic Focus GX-650',          'seasonic',    10000, 650],
            ['Seasonic Focus GX-750',          'seasonic',    12000, 750],
            ['Seasonic Focus GX-850',          'seasonic',    14000, 850],
            ['Seasonic Prime TX-1000',         'seasonic',    28000, 1000],
            ['Seasonic Prime TX-1300',         'seasonic',    45000, 1300],
            ['be quiet! Pure Power 11 500W',   'bequiet',     6000,  500],
            ['be quiet! Straight Power 11 750W','bequiet',    13000, 750],
            ['be quiet! Dark Power 13 1000W',  'bequiet',     30000, 1000],
            ['Thermaltake Toughpower GF3 850W','thermaltake', 15000, 850],
            ['Thermaltake Toughpower GF3 1000W','thermaltake',19000, 1000],
            ['Cooler Master MWE 650 Bronze',   'coolermaster',6000,  650],
            ['Cooler Master MWE 850 Gold',     'coolermaster',11000, 850],
            ['DeepCool PK650D',                'deepcool',    5500,  650],
            ['DeepCool PQ1000M',               'deepcool',    16000, 1000],
            ['Silverstone SX700-G',            'silverstone', 12500, 700],
            ['Silverstone ST1200-PTS',         'silverstone', 28000, 1200],
            ['Arctic P-750',                   'arctic',      9500,  750],
            ['Fractal Ion+ 2 Platinum 860W',   'fractal',     16000, 860],
        ];

        foreach ($data as [$name, $brand, $price, $power]) {
            $this->make('psu', $brand, $name, $price, 'psuSpec', [
                'power' => $power,
            ]);
        }
    }

    // ============================================================
    // Cases — spec: form_factor, color
    // ============================================================
    private function seedCases(): void
    {
        $this->wipe('pc_case');

        $data = [
            ['Fractal Design Node 304',        'fractal',     9500,  'mini-itx', 'black'],
            ['Fractal Design Ridge',           'fractal',     14000, 'mini-itx', 'black'],
            ['Cooler Master NR200P',           'coolermaster',10500, 'mini-itx', 'black'],
            ['Cooler Master NR200P White',     'coolermaster',11500, 'mini-itx', 'white'],
            ['NZXT H210i',                     'nzxt',        11000, 'mini-itx', 'black'],
            ['Lian Li TU150',                  'lianli',      13000, 'mini-itx', 'black'],

            ['NZXT H5 Flow',                   'nzxt',        9500,  'mid-tower', 'black'],
            ['NZXT H5 Flow White',             'nzxt',        10000, 'mid-tower', 'white'],
            ['NZXT H7 Flow',                   'nzxt',        13500, 'mid-tower', 'black'],
            ['NZXT H7 Flow White',             'nzxt',        14500, 'mid-tower', 'white'],
            ['Fractal Design Meshify 2',       'fractal',     15500, 'mid-tower', 'black'],
            ['Fractal Design Pop Air',         'fractal',     9500,  'mid-tower', 'black'],
            ['Fractal Design North',           'fractal',     16500, 'mid-tower', 'black'],
            ['Corsair 4000D Airflow',          'corsair',     10500, 'mid-tower', 'black'],
            ['Corsair 4000D Airflow White',    'corsair',     11500, 'mid-tower', 'white'],
            ['Corsair 5000D Airflow',          'corsair',     15500, 'mid-tower', 'black'],
            ['Cooler Master MasterBox TD500',  'coolermaster',9500,  'mid-tower', 'black'],
            ['Cooler Master MasterBox TD500 White','coolermaster',10500,'mid-tower','white'],
            ['Lian Li Lancool 216',            'lianli',      10500, 'mid-tower', 'black'],
            ['Lian Li Lancool 216 White',      'lianli',      11500, 'mid-tower', 'white'],
            ['Lian Li Lancool III',            'lianli',      16500, 'mid-tower', 'black'],

            ['Lian Li O11 Dynamic EVO',        'lianli',      17500, 'aquarium', 'black'],
            ['Lian Li O11 Dynamic EVO White',  'lianli',      18500, 'aquarium', 'white'],
            ['Lian Li O11 Vision',             'lianli',      21500, 'aquarium', 'black'],
            ['Lian Li O11 Vision White',       'lianli',      22500, 'aquarium', 'white'],
            ['NZXT H9 Flow',                   'nzxt',        16500, 'aquarium', 'black'],
            ['NZXT H9 Flow White',             'nzxt',        17500, 'aquarium', 'white'],
            ['NZXT H9 Elite',                  'nzxt',        24000, 'aquarium', 'black'],
            ['Corsair 6500X',                  'corsair',     18000, 'aquarium', 'black'],
            ['Corsair 6500X White',            'corsair',     19000, 'aquarium', 'white'],
            ['Corsair 3500X',                  'corsair',     11500, 'aquarium', 'black'],
            ['Lian Li O11D Mini',              'lianli',      12500, 'aquarium', 'black'],
            ['DeepCool CH560 Digital',         'deepcool',    10500, 'aquarium', 'black'],
            ['DeepCool CH560 Digital White',   'deepcool',    11500, 'aquarium', 'white'],
        ];

        foreach ($data as [$name, $brand, $price, $form, $color]) {
            $this->make('pc_case', $brand, $name, $price, 'caseSpec', [
                'form_factor' => $form,
                'color'       => $color,
            ]);
        }
    }

    // ============================================================
    // Cooling — spec: type, tdp
    // ============================================================
    private function seedCooling(): void
    {
        $this->wipe('cooling');

        $data = [
            ['DeepCool AK400',                  'deepcool',  3200,  'air', 220],
            ['DeepCool AK400 Digital',          'deepcool',  4200,  'air', 220],
            ['DeepCool AK620',                  'deepcool',  6500,  'air', 260],
            ['DeepCool AK620 Digital',          'deepcool',  7500,  'air', 260],
            ['DeepCool Assassin IV',            'deepcool',  12500, 'air', 280],
            ['Noctua NH-U12S Redux',            'noctua',    6500,  'air', 180],
            ['Noctua NH-U12A',                  'noctua',    12500, 'air', 220],
            ['Noctua NH-D15',                   'noctua',    14500, 'air', 250],
            ['Noctua NH-D15 chromax.black',     'noctua',    16500, 'air', 250],
            ['Noctua NH-D15 G2',                'noctua',    21000, 'air', 280],
            ['be quiet! Pure Rock 2',           'bequiet',   4500,  'air', 150],
            ['be quiet! Dark Rock 4',           'bequiet',   7500,  'air', 200],
            ['be quiet! Dark Rock Pro 4',       'bequiet',   10500, 'air', 250],
            ['be quiet! Dark Rock Elite',       'bequiet',   14500, 'air', 270],
            ['Arctic Freezer 34 eSports DUO',   'arctic',    4200,  'air', 210],
            ['Arctic Freezer 36 A-RGB',         'arctic',    5500,  'air', 220],
            ['Arctic Freezer 36 CO',            'arctic',    4800,  'air', 200],
            ['ID-Cooling SE-214-XT',            'idcooling', 2200,  'air', 180],
            ['ID-Cooling SE-224-XT',            'idcooling', 2800,  'air', 180],
            ['Cooler Master Hyper 212 Black',   'coolermaster',3500,'air', 150],
            ['Thermaltake Peerless Assassin 120','thermaltake',3800,'air', 245],

            ['Arctic Liquid Freezer III 240',   'arctic',    9500,  'water', 250],
            ['Arctic Liquid Freezer III 280',   'arctic',    11500, 'water', 300],
            ['Arctic Liquid Freezer III 360',   'arctic',    13500, 'water', 350],
            ['Arctic Liquid Freezer III 420',   'arctic',    15500, 'water', 400],
            ['NZXT Kraken 240',                 'nzxt',      15500, 'water', 280],
            ['NZXT Kraken 280',                 'nzxt',      18000, 'water', 300],
            ['NZXT Kraken 360',                 'nzxt',      21000, 'water', 350],
            ['NZXT Kraken Elite 360',           'nzxt',      32000, 'water', 400],
            ['Corsair iCUE H100i Elite Capellix','corsair',  17500, 'water', 250],
            ['Corsair iCUE H150i Elite Capellix','corsair',  23000, 'water', 320],
            ['Lian Li Galahad II Trinity 360',  'lianli',    16500, 'water', 350],
            ['Cooler Master MasterLiquid ML240', 'coolermaster',8500,'water', 200],
            ['Cooler Master MasterLiquid ML360', 'coolermaster',13500,'water', 300],
            ['DeepCool LT720',                  'deepcool',  12500, 'water', 350],
            ['DeepCool LS720',                  'deepcool',  14500, 'water', 350],
            ['Thermaltake TOUGHLIQUID 360 ARGB','thermaltake',15500,'water', 300],
        ];

        foreach ($data as [$name, $brand, $price, $type, $tdp]) {
            $this->make('cooling', $brand, $name, $price, 'coolingSpec', [
                'type' => $type,
                'tdp'  => $tdp,
            ]);
        }
    }
}