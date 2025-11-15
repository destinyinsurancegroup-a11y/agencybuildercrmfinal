<?php

namespace App\Livewire\Contacts;

use App\Models\Contact;
use App\Models\ContactNote;
use Livewire\Component;

class ContactDetails extends Component
{
    public $contact;
    public $editing = false;
    public $newNote = '';

    public $full_name;
    public $email;
    public $phone;
    public $address_line1;
    public $address_line2;
    public $city;
    public $state;
    public $postal_code;
    public $date_of_birth;
    public $policy_type;
    public $face_amount;
    public $premium_amount;
    public $premium_due_date;

    protected $listeners = [
        'contactSelected' => 'loadContact'
    ];

    public function loadContact($id)
    {
        $this->contact = Contact::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);

        $this->full_name = $this->contact->full_name;
        $this->email = $this->contact->email;
        $this->phone = $this->contact->phone;
        $this->address_line1 = $this->contact->address_line1;
        $this->address_line2 = $this->contact->address_line2;
        $this->city = $this->contact->city;
        $this->state = $this->contact->state;
        $this->postal_code = $this->contact->postal_code;
        $this->date_of_birth = $this->contact->date_of_birth;
        $this->policy_type = $this->contact->policy_type;
        $this->face_amount = $this->contact->face_amount;
        $this->premium_amount = $this->contact->premium_amount;
        $this->premium_due_date = $this->contact->premium_due_date;
    }

    public function save()
    {
        $this->contact->update([
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address_line1' => $this->address_line1,
            'address_line2' => $this->address_line2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'date_of_birth' => $this->date_of_birth,
            'policy_type' => $this->policy_type,
            'face_amount' => $this->face_amount,
            'premium_amount' => $this->premium_amount,
            'premium_due_date' => $this->premium_due_date,
        ]);

        $this->dispatch('contactUpdated');
        $this->editing = false;
    }

    public function addNote()
    {
        ContactNote::create([
            'tenant_id' => auth()->user()->tenant_id,
            'contact_id' => $this->contact->id,
            'created_by' => auth()->id(),
            'body' => $this->newNote,
        ]);

        $this->newNote = '';
        $this->contact->refresh();
    }

    public function render()
    {
        return view('livewire.contacts.contact-details', [
            'notes' => $this->contact?->notes()->orderByDesc('created_at')->get() ?? collect()
        ]);
    }
}
