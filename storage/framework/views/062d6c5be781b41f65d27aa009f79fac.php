<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Carte Scolaire - <?php echo e($eleve->matricule); ?></title>
    <style>
        @page { 
            margin: 0; 
            size: 85.6mm 53.98mm;
            padding: 0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            margin: 0;
            padding: 0;
            width: 85.6mm;
            height: 53.98mm;
        }
        
        body { 
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 6pt;
        }
        
        .card {
            width: 85.6mm;
            height: 53.98mm;
            background: white;
            border: 2px solid #007a3d;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(to right, #007a3d 0%, #007a3d 33%, #ce1126 33%, #ce1126 66%, #fcd116 66%, #fcd116 100%);
            height: 7mm;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            flex-shrink: 0;
        }
        
        .header-content {
            background: rgba(255, 255, 255, 0.95);
            padding: 1mm 2mm;
            border-radius: 1mm;
            text-align: center;
            font-size: 5pt;
        }
        
        .etoile {
            color: #fcd116;
            font-size: 8pt;
            font-weight: bold;
        }
        
        .republique {
            font-size: 5pt;
            font-weight: bold;
            color: #007a3d;
            text-transform: uppercase;
            line-height: 1;
        }
        
        .devise {
            font-size: 3.5pt;
            color: #ce1126;
            font-style: italic;
        }
        
        .body {
            flex: 1;
            display: flex;
            padding: 1.5mm;
            gap: 1.5mm;
            position: relative;
        }
        
        .photo-section {
            flex-shrink: 0;
        }
        
        .photo-frame {
            width: 14mm;
            height: 18mm;
            border: 1.5px solid #007a3d;
            border-radius: 1mm;
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
            justify-content: space-between;
            font-size: 5pt;
        }
        
        .school-title {
            text-align: center;
            font-size: 5.5pt;
            font-weight: bold;
            color: #ce1126;
            text-transform: uppercase;
            line-height: 1;
        }
        
        .info-row {
            display: flex;
            gap: 0.8mm;
            line-height: 1;
        }
        
        .info-label {
            font-weight: bold;
            color: #007a3d;
            min-width: 9mm;
            flex-shrink: 0;
        }
        
        .info-value {
            color: #1f2937;
            flex: 1;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .matricule-box {
            background: #fcd116;
            padding: 0.2mm 0.6mm;
            border-radius: 0.4mm;
            font-weight: bold;
            color: #000;
            border: 0.5px solid #007a3d;
            display: inline-block;
            font-size: 5pt;
        }
        
        .classe-badge {
            background: #007a3d;
            color: white;
            padding: 0.2mm 0.6mm;
            border-radius: 0.4mm;
            font-weight: bold;
            display: inline-block;
            font-size: 5pt;
        }
        
        .qr-section {
            position: absolute;
            bottom: 5mm;
            right: 1.5mm;
            width: 10mm;
            height: 10mm;
            background: white;
            border: 1px solid #007a3d;
            border-radius: 0.5mm;
            padding: 0.3mm;
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
            padding: 0.8mm 1.5mm;
            display: flex;
            justify-content: space-between;
            font-size: 3.5pt;
            border-top: 1px solid #005a2d;
            flex-shrink: 0;
            line-height: 1;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <div class="header-content">
                <div class="etoile">★</div>
                <div class="republique">République du Cameroun</div>
                <div class="devise">Paix - Travail - Patrie</div>
            </div>
        </div>
        
        <div class="body">
            <div class="photo-section">
                <div class="photo-frame">
                    <?php if($photoBase64): ?>
                        <img src="<?php echo e($photoBase64); ?>" alt="Photo">
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="info-section">
                <div class="school-title"><?php echo e($etablissement->nom); ?></div>
                
                <div class="info-row">
                    <div class="info-label">Matricule :</div>
                    <div class="info-value"><span class="matricule-box"><?php echo e($eleve->matricule); ?></span></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Nom :</div>
                    <div class="info-value"><?php echo e(strtoupper($eleve->nom)); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Prénom :</div>
                    <div class="info-value"><?php echo e(ucwords(strtolower($eleve->prenom))); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Classe :</div>
                    <div class="info-value"><span class="classe-badge"><?php echo e($classe->nom); ?></span></div>
                </div>
            </div>
            
            <div class="qr-section">
                <div class="qr-code"><?php echo $qrCode; ?></div>
            </div>
        </div>
        
        <div class="footer">
            <div>Délivré: <?php echo e(now()->format('d/m/Y')); ?></div>
            <div style="text-align: center; flex: 1;">CARTE OFFICIELLE</div>
            <div style="text-align: right;">Expire: <?php echo e(now()->addYears(2)->format('d/m/Y')); ?></div>
        </div>
    </div>
</body>
</html>
<?php /**PATH D:\LICENCE\projets\apppcs_project\apppcs_project\apppcs_backend\resources\views/cartes/modele-cameroun.blade.php ENDPATH**/ ?>