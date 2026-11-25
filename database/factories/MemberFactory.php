<?php

namespace Database\Factories;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'father_name' => fake()->name('male'),
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(Member::GENDERS),
            'blood_group' => fake()->randomElement(Member::BLOOD_GROUPS),
            'mobile' => fake()->numerify('98########'),
            'whatsapp' => fake()->numerify('97########'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'district' => fake()->city(),
            'work_area' => fake()->citySuffix() . ' ' . fake()->city(),
            'state' => fake()->state(),
            'pincode' => fake()->numerify('######'),
            'occupation' => fake()->jobTitle(),
        ];
    }

    /** Adds a random Aadhaar number (encrypted by the model). */
    public function withAadhaar(?string $number = null): static
    {
        return $this->afterMaking(function (Member $member) use ($number) {
            $member->setAadhaar($number ?? fake()->numerify('############'));
        });
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => Member::STATUS_APPROVED]);
    }
}
