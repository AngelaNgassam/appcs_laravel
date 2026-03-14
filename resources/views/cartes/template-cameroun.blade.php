<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Carte Scolaire - {{ $eleve->matricule }}</title>
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
            line-height: 1.3;
            width: 85.6mm;
            height: 53.98mm;
        }
        
        .card-container {
            width: 100%;
            height: 100%;
            position: relative;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 2px solid #10B981;
            border-radius: 3mm;
            overflow: hidden;
        }
        
        /* En-tête moderne */
        .card-header {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            padding: 2mm 3mm;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #047857;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 2mm;
        }
        
        .logo-mini {
            width: 10mm;
            height: 10mm;
            background: white;
            border-radius: 2mm;
            padding: 1mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logo-mini img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .header-text {
            color: white;
        }
        
        .school-name {
            font-size: 8pt;
            font-weight: bold;
            line-height: 1.2;
            text-transform: uppercase;
        }
        
        .card-subtitle {
            font-size: 5pt;
            opacity: 0.9;
            margin-top: 0.5mm;
        }
        
        .header-right {
            text-align: right;
        }
        
        .year-badge {
            background: white;
            color: #10B981;
            padding: 1mm 2mm;
            border-radius: 2mm;
            font-size: 7pt;
            font-weight: bold;
        }
        
        /* Corps de la carte */
        .card-body {
            padding: 3mm;
            display: flex;
            gap: 3mm;
            height: calc(100% - 14mm - 8mm);
        }
        
        /* Section photo */
        .photo-section {
            flex-shrink: 0;
        }
        
        .photo-frame {
            width: 22mm;
            height: 28mm;
            border: 2px solid #10B981;
            border-radius: 2mm;
            overflow: hidden;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* Section informations */
        .info-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 1.5mm;
        }
        
        .info-row {
            display: flex;
            align-items: baseline;
            gap: 1mm;
        }
        
        .info-label {
            font-weight: 600;
            color: #047857;
            font-size: 6pt;
            min-width: 18mm;
            flex-shrink: 0;
        }
        
        .info-value {
            color: #1f2937;
            font-size: 7pt;
            font-weight: 500;
            flex: 1;
        }
        
        .matricule-box {
            background: #FCD116;
            padding: 1mm 2mm;
            border-radius: 2mm;
            font-weight: bold;
            color: #000;
            display: inline-block;
            border: 1px solid #10B981;
            font-size: 7pt;
        }
        
        .classe-badge {
            background: #10B981;
            color: white;
            padding: 1mm 2mm;
            border-radius: 2mm;
            font-weight: bold;
            display: inline-block;
            font-size: 7pt;
        }
        
        /* Section QR Code */
        .qr-section {
            flex-shrink: 0;
            text-align: center;
        }
        
        .qr-frame {
            width: 18mm;
            height: 18mm;
            background: white;
            padding: 1mm;
            border: 2px solid #10B981;
            border-radius: 2mm;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .qr-code {
            width: 100%;
            height: 100%;
        }
        
        .qr-code svg {
            width: 100% !important;
            height: 100% !important;
        }
        
        .qr-label {
            font-size: 5pt;
            color: #6b7280;
            margin-top: 1mm;
            font-weight: 600;
        }
        
        /* Pied de page */
        .card-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            padding: 1.5mm 3mm;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 2px solid #047857;
        }
        
        .footer-left {
            color: white;
            font-size: 5pt;
        }
        
        .footer-center {
            color: white;
            font-size: 6pt;
            font-weight: bold;
        }
        
        .footer-right {
            color: white;
            font-size: 5pt;
            text-align: right;
        }
        
        .email-text {
            font-size: 6pt;
            color: white;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="card-container">
        <!-- En-tête -->
        <div class="card-header">
            <div class="header-left">
                @if($logoBase64)
                <div class="logo-mini">
                    <img src="{{ $logoBase64 }}" alt="Logo">
                </div>
                @endif
                <div class="header-text">
                    <div class="school-name">{{ $etablissement->nom }}</div>
                    <div class="card-subtitle">Carte d'Identité Scolaire</div>
                </div>
            </div>
            <div class="header-right">
                <div class="year-badge">{{ $anneeAcademique }}</div>
            </div>
        </div>
        
        <!-- Corps -->
        <div class="card-body">
            <!-- Photo -->
            <div class="photo-section">
                <div class="photo-frame">
                    @if($photoBase64)
                        <img src="{{ $photoBase64 }}" alt="Photo élève" class="photo">
                    @endif
                </div>
            </div>
            
            <!-- Informations -->
            <div class="info-section">
                <div class="info-row">
                    <div class="info-label">Matricule :</div>
                    <div class="info-value">
                        <span class="matricule-box">{{ $eleve->matricule }}</span>
                    </div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Nom :</div>
                    <div class="info-value">{{ strtoupper($eleve->nom) }}</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Prénom(s) :</div>
                    <div class="info-value">{{ ucwords(strtolower($eleve->prenom)) }}</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Né(e) le :</div>
                    <div class="info-value">{{ $eleve->date_naissance ? $eleve->date_naissance->format('d/m/Y') : 'N/A' }}</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Lieu :</div>
                    <div class="info-value">{{ $eleve->lieu_naissance ?? 'N/A' }}</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Sexe :</div>
                    <div class="info-value">{{ $eleve->sexe === 'M' ? 'Masculin' : 'Féminin' }}</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Classe :</div>
                    <div class="info-value">
                        <span class="classe-badge">{{ $classe->nom }}</span>
                    </div>
                </div>
                
                @if($eleve->email)
                <div class="info-row">
                    <div class="info-label">Email :</div>
                    <div class="info-value" style="font-size: 6pt;">{{ $eleve->email }}</div>
                </div>
                @endif
            </div>
            
            <!-- QR Code -->
            <div class="qr-section">
                <div class="qr-frame">
                    <div class="qr-code">{!! $qrCode !!}</div>
                </div>
                <div class="qr-label">Scan QR</div>
            </div>
        </div>
        
        <!-- Pied de page -->
        <div class="card-footer">
            <div class="footer-left">
                Délivré le: {{ now()->format('d/m/Y') }}
            </div>
            <div class="footer-center">
                @if($eleve->email)
                <div class="email-text">{{ $eleve->email }}</div>
                @else
                <div class="email-text">{{ $etablissement->email ?? 'contact@etablissement.cm' }}</div>
                @endif
            </div>
            <div class="footer-right">
                Expire le: {{ now()->addYears(2)->format('d/m/Y') }}
            </div>
        </div>
    </div>
</body>
</html>
