<?php
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserAddress;
class UserAddressesTableSeeder extends Seeder
{
    public function run()
    {
        // 获取 Faker 实例
        $faker = app(Faker\Generator::class);
        $addresses = factory(UserAddress::class)
            ->times(3)
            ->make()
            ->each(function ($address, $index)
            {
                $address->user_id = 1;
            });
        UserAddress::insert($addresses->toArray());
    }
}
