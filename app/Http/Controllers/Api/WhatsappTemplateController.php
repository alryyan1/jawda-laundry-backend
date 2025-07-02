<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\WhatsappTemplate;
use Illuminate\Http\Request;
use App\Http\Resources\WhatsappTemplateResource;

class WhatsappTemplateController extends Controller {
    public function __construct() {
        $this->middleware('can:settings:manage-application'); // Protected by a general admin permission
    }
    
    public function index() {
        // Get all templates, keyed by status for easy frontend access
        $templates = WhatsappTemplate::all()->keyBy('status');
        return WhatsappTemplateResource::collection($templates);
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'status' => 'required|string|unique:whatsapp_templates,status',
            'message_template' => 'required|string',
            'is_active' => 'required|boolean',
            'attach_invoice' => 'required|boolean',
        ]);
        $template = WhatsappTemplate::create($validated);
        return new WhatsappTemplateResource($template);
    }

    public function update(Request $request, WhatsappTemplate $whatsappTemplate) {
        $validated = $request->validate([
            'message_template' => 'sometimes|required|string',
            'is_active' => 'sometimes|required|boolean',
            'attach_invoice' => 'sometimes|required|boolean',
        ]);
        $whatsappTemplate->update($validated);
        return new WhatsappTemplateResource($whatsappTemplate);
    }
}