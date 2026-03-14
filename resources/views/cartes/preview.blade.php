<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prévisualisation - {{ $eleve->matricule }}</title>
    <style>
        @page {
            margin: 0;
            padding: 0;
            size: 85.6mm 53.98mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            margin: 0;
            padding: 0;
        
        .container {
            width: 100%;
            max-width: 600px;
        }
        
        .card {
            width: 100%;
            aspect-ratio: 85.6 / 53.98;
            background: white;
            border: 3px solid #007a3d;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            position: relative;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(to right, #007a3d 0%, #007a3d 33%, #ce1126 33%, #ce1126 66%, #fcd116 66%, #fcd116 100%);
            height: 18%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 0 3%;
        }
        
        .header-content {
            background: rgba(255, 255, 255, 0.95);
            padding: 2% 4%;
            border-radius: 4px;
            text-align: center;
            display: flex;
            align-items: center;
            gap: 3%;
        }
        
        .header-logo {
            width: 12%;
            aspect-ratio: 1;
            flex-shrink: 0;
        }
        
        .header-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .header-text {
            flex: 1;
        }
        
        .etoile {
            color: #fcd116;
            font-size: 24px;
            font-weight: bold;
        }
        
        .republique {
            font-size: 14px;
            font-weight: bold;
            color: #007a3d;
            text-transform: uppercase;
            margin: 2px 0;
        }
        
        .devise {
            font-size: 10px;
            color: #ce1126;
            font-style: italic;
        }
        
        .body {
            flex: 1;
            display: flex;
            padding: 3%;
            gap: 3%;
        }
        
        .photo-section {
            flex-shrink: 0;
            width: 28%;
        }
        
        .photo-frame {
            width: 100%;
            aspect-ratio: 1;
            border: 3px solid #007a3d;
            border-radius: 6px;
            overflow: hidden;
            background: #f9fafb;
        }
        
        .photo-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .info-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-around;
            font-size: 13px;
        }
        
        .school-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            color: #ce1126;
            margin-bottom: 2%;
            text-transform: uppercase;
        }
        
        .info-row {
            display: flex;
            gap: 2%;
            align-items: center;
        }
        
        .info-label {
            font-weight: bold;
            color: #007a3d;
            min-width: 35%;
        }
        
        .info-value {
            color: #1f2937;
            flex: 1;
        }
        
        .matricule-box {
            background: #fcd116;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
            color: #000;
            display: inline-block;
        }
        
        .classe-badge {
            background: #007a3d;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
        }
        
        .qr-section {
            position: absolute;
            bottom: 12%;
            right: 3%;
            width: 18%;
            aspect-ratio: 1;
            background: white;
            border: 2px solid #007a3d;
            border-radius: 4px;
            padding: 2%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .qr-code {
            width: 100%;
            height: 100%;
        }
        
        .qr-code svg {
            width: 100% !important;
            height: 100% !important;
        }
        
        .footer {
            background: #007a3d;
            color: white;
            padding: 2% 3%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
            height: 12%;
            align-items: center;
            gap: 1%;
        }
        
        .footer-top {
            display: flex;
            justify-content: space-between;
            width: 100%;
        }
        
        .footer-email {
            text-align: center;
            font-size: 9px;
        }
        
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #007a3d;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }
        
        .print-btn:hover {
            background: #005a2d;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .print-btn {
                display: none;
            }
            
            .container {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">🖨️ Imprimer</button>
    
    <div class="container">
        <div class="card">
            <!-- En-tête avec drapeau -->
            <div class="header">
                <div class="header-content">
                    @if($logoBase64)
                    <div class="header-logo">
                        <img src="{{ $logoBase64 }}" alt="Logo">
                    </div>
                    @endif
                    <div class="header-text">
                        <div class="etoile">★</div>
                        <div class="republique">République du Cameroun</div>
                        <div class="devise">Paix - Travail - Patrie</div>
                    </div>
                </div>
            </div>
            
            <!-- Corps -->
            <div class="body">
                <!-- Photo -->
                <div class="photo-section">
                    <div class="photo-frame">
                        @if($photoBase64)
                            <img src="{{ $photoBase64 }}" alt="Photo">
                        @endif
                    </div>
                </div>
                
                <!-- Infos -->
                <div class="info-section">
                    <div class="school-title">{{ $etablissement->nom }}</div>
                    
                    <div class="info-row">
                        <div class="info-label">Matricule :</div>
                        <div class="info-value"><span class="matricule-box">{{ $eleve->matricule }}</span></div>
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
                        <div class="info-label">Classe :</div>
                        <div class="info-value"><span class="classe-badge">{{ $classe->nom }}</span></div>
                    </div>
                </div>
                
                <!-- QR Code -->
                <div class="qr-section">
                    <div class="qr-code">{!! $qrCode !!}</div>
                </div>
            </div>
            
            <!-- Pied de page -->
            <div class="footer">
                <div class="footer-top">
                    <div>Délivré: {{ now()->format('d/m/Y') }}</div>
                    <div>CARTE SCOLAIRE OFFICIELLE</div>
                    <div>Expire: {{ now()->addYears(2)->format('d/m/Y') }}</div>
                </div>
                @if($etablissement->email)
                <div class="footer-email">📧 {{ $etablissement->email }}</div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
