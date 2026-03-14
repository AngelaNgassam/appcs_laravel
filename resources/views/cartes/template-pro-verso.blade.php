<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Carte Scolaire Verso - {{ $eleve->matricule }}</title>
    <style>
        @page { 
            margin: 0; 
            size: 85.6mm 53.98mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 7pt;
            line-height: 1.4;
            width: 85.6mm;
            height: 53.98mm;
        }
        
        .card-verso {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            border: 2px solid #4a5568;
            border-radius: 8px;
            padding: 10px;
            position: relative;
            color: white;
        }
        
        .verso-header {
            text-align: center;
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .verso-title {
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 3px;
        }
        
        .school-info {
            background: rgba(255, 255, 255, 0.95);
            padding: 8px;
            border-radius: 6px;
            margin-bottom: 8px;
            color: #1f2937;
        }
        
        .info-line {
            margin-bottom: 3px;
            font-size: 7pt;
            display: flex;
            align-items: flex-start;
        }
        
        .info-icon {
            font-weight: bold;
            margin-right: 5px;
            color: #7c3aed;
            min-width: 12px;
        }
        
        .info-text {
            flex: 1;
        }
        
        .emergency-section {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 6px;
            border-radius: 4px;
            margin-bottom: 6px;
        }
        
        .emergency-title {
            font-weight: bold;
            color: #fef2f2;
            font-size: 7pt;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        
        .emergency-contact {
            color: #fef2f2;
            font-size: 7pt;
        }
        
        .signature-section {
            display: table;
            width: 100%;
            margin-top: 6px;
        }
        
        .signature-box {
            display: table-cell;
            width: 48%;
            text-align: center;
        }
        
        .signature-box:first-child {
            padding-right: 4px;
        }
        
        .signature-label {
            font-size: 6pt;
            margin-bottom: 2px;
            color: #fef2f2;
        }
        
        .signature-line {
            border-top: 1px solid rgba(255, 255, 255, 0.5);
            height: 25px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }
        
        .signature-image {
            max-height: 23px;
            max-width: 100%;
        }
        
        .footer-note {
            position: absolute;
            bottom: 5px;
            left: 10px;
            right: 10px;
            text-align: center;
            font-size: 5pt;
            color: rgba(255, 255, 255, 0.7);
            font-style: italic;
        }
        
        .qr-mini {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 35px;
            height: 35px;
            background: white;
            padding: 2px;
            border-radius: 3px;
        }
        
        .qr-mini svg {
            width: 100% !important;
            height: 100% !important;
        }
    </style>
</head>
<body>
    <div class="card-verso">
        <!-- Mini QR Code -->
        <div class="qr-mini">{!! $qrCode !!}</div>
        
        <!-- En-tête -->
        <div class="verso-header">
            <div class="verso-title">Informations Établissement</div>
        </div>
        
        <!-- Informations établissement -->
        <div class="school-info">
            <div class="info-line">
                <span class="info-icon">📍</span>
                <span class="info-text">{{ $etablissement->adresse ?? 'Adresse non renseignée' }}, {{ $etablissement->ville ?? '' }}</span>
            </div>
            <div class="info-line">
                <span class="info-icon">📞</span>
                <span class="info-text">{{ $etablissement->telephone ?? 'Téléphone non renseigné' }}</span>
            </div>
            @if($etablissement->email)
            <div class="info-line">
                <span class="info-icon">✉️</span>
                <span class="info-text">{{ $etablissement->email }}</span>
            </div>
            @endif
        </div>
        
        <!-- Contact d'urgence -->
        <div class="emergency-section">
            <div class="emergency-title">⚠️ Contact d'urgence</div>
            <div class="emergency-contact">
                @if($eleve->nom_parent)
                    <strong>{{ $eleve->nom_parent }}</strong><br>
                @endif
                {{ $eleve->contact_parent ?? 'Non renseigné' }}
            </div>
        </div>
        
        <!-- Signatures -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-label">Signature du Responsable</div>
                <div class="signature-line">
                    @if(isset($signatureResponsable) && $signatureResponsable)
                        <img src="{{ $signatureResponsable }}" alt="Signature" class="signature-image">
                    @endif
                </div>
            </div>
            <div class="signature-box">
                <div class="signature-label">Signature de l'Élève</div>
                <div class="signature-line"></div>
            </div>
        </div>
        
        <!-- Note de bas de page -->
        <div class="footer-note">
            Cette carte est strictement personnelle et doit être présentée à toute demande.<br>
            En cas de perte, veuillez contacter immédiatement l'établissement.
        </div>
    </div>
</body>
</html>
