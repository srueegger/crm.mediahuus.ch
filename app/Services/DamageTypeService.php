<?php
declare(strict_types=1);

namespace App\Services;

class DamageTypeService
{
    /**
     * Available damage types with their display names
     */
    public const DAMAGE_TYPES = [
        'battery_damage' => 'Akkuschaden',
        'screen_damage' => 'Display-/Touchscreen-Schaden',
        'water_damage' => 'Wasserschaden',
        'total_damage' => 'Totalschaden',
        'charging_port' => 'Ladebuchse-Problem',
        'speaker_microphone' => 'Lautsprecher-/Mikrofon-Problem',
        'camera_damage' => 'Kameraschaden',
        'button_damage' => 'Tastendefekt',
        'software_issue' => 'Software-Problem',
        'other' => 'Sonstiges'
    ];

    /**
     * Predefined text templates for each damage type
     */
    private const DAMAGE_TEMPLATES = [
        'battery_damage' => 'Diagnose zeigt einen defekten Akku. Der Akku verliert schnell an Ladung oder lädt nicht mehr korrekt. Wir empfehlen den Austausch des Originalakkus durch ein hochwertiges Ersatzteil.',
        
        'screen_damage' => 'Das Display und/oder der Touchscreen weisen Beschädigungen auf. Sichtbare Risse, schwarze Bereiche oder fehlende Touch-Funktionalität beeinträchtigen die Nutzung. Wir führen eine professionelle Display-Reparatur mit Originalteilen durch.',
        
        'water_damage' => 'Das Gerät zeigt Anzeichen von Wasserschäden. Korrosion und Kurzschlüsse können diverse Komponenten betreffen. Eine umfassende Reinigung und Reparatur der betroffenen Teile ist erforderlich.',
        
        'total_damage' => 'Das Gerät weist multiple schwere Beschädigungen auf, die eine wirtschaftliche Reparatur nicht mehr sinnvoll machen. Die Reparaturkosten würden den Gerätewert übersteigen.',
        
        'charging_port' => 'Die Ladebuchse funktioniert nicht ordnungsgemäss. Das Gerät lädt nicht oder nur bei bestimmter Kabelposition. Reinigung oder Austausch der Ladebuchse ist notwendig.',
        
        'speaker_microphone' => 'Lautsprecher und/oder Mikrofon funktionieren nicht korrekt. Gestörte Audioausgabe oder Probleme bei Telefonaten. Austausch der entsprechenden Komponenten erforderlich.',
        
        'camera_damage' => 'Die Kamera-Funktion ist beeinträchtigt. Unscharfe Bilder, schwarzer Bildschirm oder Fehlermeldungen beim Öffnen der Kamera-App. Reparatur oder Austausch der Kamera-Einheit notwendig.',
        
        'button_damage' => 'Home-Button, Ein/Aus-Taste oder Lautstärke-Tasten reagieren nicht oder nur unzuverlässig. Reinigung oder Austausch der defekten Tasten-Mechanik erforderlich.',
        
        'software_issue' => 'Software-bezogene Probleme wie Systemabstürze, langsame Performance oder fehlerhafte App-Funktionen. Neuinstallation des Betriebssystems oder Software-Optimierung notwendig.',
        
        'other' => 'Individuelle Problematik, die eine spezifische Diagnose und Reparatur erfordert. Detaillierte Fehleranalyse und entsprechende Reparaturmassnahmen werden durchgeführt.'
    ];

    /**
     * Get all available damage types
     */
    public static function getDamageTypes(): array
    {
        return self::DAMAGE_TYPES;
    }

    /**
     * Get display name for a damage type
     */
    public static function getDamageTypeLabel(string $damageType): string
    {
        return self::DAMAGE_TYPES[$damageType] ?? 'Unbekannt';
    }

    /**
     * Get predefined text template for damage type
     */
    public static function getDamageTemplate(string $damageType): string
    {
        return self::DAMAGE_TEMPLATES[$damageType] ?? self::DAMAGE_TEMPLATES['other'];
    }

    /**
     * Validate if damage type is valid
     */
    public static function isValidDamageType(string $damageType): bool
    {
        return array_key_exists($damageType, self::DAMAGE_TYPES);
    }

    /**
     * Get combined text: template + custom text
     */
    public static function getCombinedIssueText(string $damageType, string $customText = ''): string
    {
        $template = self::getDamageTemplate($damageType);
        
        if (empty($customText)) {
            return $template;
        }
        
        return $template . "\n\nZusätzliche Bemerkungen:\n" . $customText;
    }

    /**
     * Get all damage templates for frontend
     */
    public static function getDamageTemplates(): array
    {
        return self::DAMAGE_TEMPLATES;
    }
}