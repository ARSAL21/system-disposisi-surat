import os
import zipfile

def generate_letter_template(output_path: str):
    # Ensure directory exists
    os.makedirs(os.path.dirname(output_path), exist_ok=True)
    
    # [Content_Types].xml
    content_types_xml = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
</Types>"""

    # _rels/.rels
    root_rels_xml = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>"""

    # word/_rels/document.xml.rels
    doc_rels_xml = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>"""

    # word/styles.xml
    styles_xml = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:docDefaults>
    <w:rPrDefault>
      <w:rPr>
        <w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/>
        <w:sz w:val="22"/>
        <w:szCs w:val="22"/>
        <w:lang w:val="id-ID"/>
      </w:rPr>
    </w:rPrDefault>
    <w:pPrDefault>
      <w:pPr>
        <w:spacing w:line="276" w:lineRule="auto" w:after="120"/>
      </w:pPr>
    </w:pPrDefault>
  </w:docDefaults>
</w:styles>"""

    # word/document.xml
    # Formatted standard Indonesian Government official letter with signature block on the bottom right
    document_xml = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
            xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <w:body>
    <!-- KOP SURAT PEMERINTAH KOTA & BAGIAN -->
    <w:p>
      <w:pPr>
        <w:jc w:val="center"/>
        <w:spacing w:before="0" w:after="20" w:line="240" w:lineRule="auto"/>
      </w:pPr>
      <w:r>
        <w:rPr>
          <w:b/>
          <w:sz w:val="24"/>
          <w:szCs w:val="24"/>
        </w:rPr>
        <w:t>PEMERINTAH KOTA BAUBAU</w:t>
      </w:r>
    </w:p>
    <w:p>
      <w:pPr>
        <w:jc w:val="center"/>
        <w:spacing w:before="0" w:after="20" w:line="240" w:lineRule="auto"/>
      </w:pPr>
      <w:r>
        <w:rPr>
          <w:b/>
          <w:sz w:val="28"/>
          <w:szCs w:val="28"/>
        </w:rPr>
        <w:t>SEKRETARIAT DAERAH</w:t>
      </w:r>
    </w:p>
    <w:p>
      <w:pPr>
        <w:jc w:val="center"/>
        <w:spacing w:before="0" w:after="40" w:line="240" w:lineRule="auto"/>
      </w:pPr>
      <w:r>
        <w:rPr>
          <w:b/>
          <w:sz w:val="26"/>
          <w:szCs w:val="26"/>
        </w:rPr>
        <w:t>BAGIAN UMUM</w:t>
      </w:r>
    </w:p>
    <w:p>
      <w:pPr>
        <w:jc w:val="center"/>
        <w:spacing w:before="0" w:after="60" w:line="220" w:lineRule="auto"/>
      </w:pPr>
      <w:r>
        <w:rPr>
          <w:sz w:val="18"/>
          <w:szCs w:val="18"/>
        </w:rPr>
        <w:t>Jalan Raya Palagimata Nomor 1, Kota Baubau, Sulawesi Tenggara</w:t>
      </w:r>
    </w:p>
    <w:p>
      <w:pPr>
        <w:jc w:val="center"/>
        <w:spacing w:before="0" w:after="160" w:line="220" w:lineRule="auto"/>
        <w:pBdr>
          <w:bottom w:val="double" w:sz="18" w:space="4" w:color="000000"/>
        </w:pBdr>
      </w:pPr>
      <w:r>
        <w:rPr>
          <w:sz w:val="18"/>
          <w:szCs w:val="18"/>
        </w:rPr>
        <w:t>Laman: www.baubaukota.go.id • Pos-el: bagian.umum@baubaukota.go.id</w:t>
      </w:r>
    </w:p>

    <!-- HEADER DATA SURAT (Tabel Tanpa Garis: Nomor/Sifat/Lampiran di Kiri, Tanggal di Kanan) -->
    <w:tbl>
      <w:tblPr>
        <w:tblW w:w="9600" w:type="dxa"/>
        <w:tblBorders>
          <w:top w:val="none"/>
          <w:left w:val="none"/>
          <w:bottom w:val="none"/>
          <w:right w:val="none"/>
          <w:insideH w:val="none"/>
          <w:insideV w:val="none"/>
        </w:tblBorders>
      </w:tblPr>
      <w:tblGrid>
        <w:gridCol w:w="1200"/>
        <w:gridCol w:w="200"/>
        <w:gridCol w:w="4600"/>
        <w:gridCol w:w="3600"/>
      </w:tblGrid>
      <w:tr>
        <w:tc><w:p><w:r><w:t>Nomor</w:t></w:r></w:p></w:tc>
        <w:tc><w:p><w:r><w:t>:</w:t></w:r></w:p></w:tc>
        <w:tc><w:p><w:r><w:b/><w:t>000 / [NO_AGENDA] / SETDA / 2026</w:t></w:r></w:p></w:tc>
        <w:tc><w:p><w:pPr><w:jc w:val="right"/></w:pPr><w:r><w:t>Baubau, [Tanggal]</w:t></w:r></w:p></w:tc>
      </w:tr>
      <w:tr>
        <w:tc><w:p><w:r><w:t>Sifat</w:t></w:r></w:p></w:tc>
        <w:tc><w:p><w:r><w:t>:</w:t></w:r></w:p></w:tc>
        <w:tc><w:p><w:r><w:t>Biasa / Segera</w:t></w:r></w:p></w:tc>
        <w:tc><w:p/></w:tc>
      </w:tr>
      <w:tr>
        <w:tc><w:p><w:r><w:t>Lampiran</w:t></w:r></w:p></w:tc>
        <w:tc><w:p><w:r><w:t>:</w:t></w:r></w:p></w:tc>
        <w:tc><w:p><w:r><w:t>- (Satu Berkas)</w:t></w:r></w:p></w:tc>
        <w:tc><w:p/></w:tc>
      </w:tr>
      <w:tr>
        <w:tc><w:p><w:r><w:t>Perihal</w:t></w:r></w:p></w:tc>
        <w:tc><w:p><w:r><w:t>:</w:t></w:r></w:p></w:tc>
        <w:tc><w:p><w:r><w:b/><w:t>[Perihal Surat / Tanggapan Resmi]</w:t></w:r></w:p></w:tc>
        <w:tc><w:p/></w:tc>
      </w:tr>
    </w:tbl>

    <!-- SPASI -->
    <w:p><w:pPr><w:spacing w:before="120" w:after="60"/></w:pPr></w:p>

    <!-- KEPADA YTH -->
    <w:p>
      <w:pPr><w:spacing w:before="60" w:after="20"/></w:pPr>
      <w:r><w:t>Kepada</w:t></w:r>
    </w:p>
    <w:p>
      <w:pPr><w:spacing w:before="0" w:after="20"/></w:pPr>
      <w:r><w:t>Yth. [Nama Jabatan / Instansi Tujuan]</w:t></w:r>
    </w:p>
    <w:p>
      <w:pPr><w:spacing w:before="0" w:after="20"/></w:pPr>
      <w:r><w:t>di -</w:t></w:r>
    </w:p>
    <w:p>
      <w:pPr><w:spacing w:before="0" w:after="160"/><w:ind w:left="400"/></w:pPr>
      <w:r><w:t>Tempat</w:t></w:r>
    </w:p>

    <!-- ISI SURAT (PARAGRAF PEMBUKA) -->
    <w:p>
      <w:pPr>
        <w:jc w:val="both"/>
        <w:spacing w:before="100" w:after="120" w:line="276" w:lineRule="auto"/>
        <w:ind w:firstLine="567"/>
      </w:pPr>
      <w:r>
        <w:t>Berdasarkan rujukan permohonan koordinasi Nomor: [Nomor_Surat_Pengirim] tanggal [Tanggal_Surat_Pengirim] perihal [Perihal_Surat_Pengirim], serta tindak lanjut hasil telaah dan koordinasi teknis perangkat daerah terkait, bersama ini disampaikan beberapa hal sebagai berikut:</w:t>
      </w:r>
    </w:p>

    <!-- ISI SURAT (POIN-POIN BATANG TUBUH) -->
    <w:p>
      <w:pPr>
        <w:jc w:val="both"/>
        <w:spacing w:before="0" w:after="80" w:line="276" w:lineRule="auto"/>
        <w:ind w:left="567" w:hanging="400"/>
      </w:pPr>
      <w:r><w:t>1. </w:t></w:r>
      <w:r><w:t>Pemerintah Kota Baubau melalui Bagian terkait menyambut baik dan mendukung terlaksananya program pengembangan yang diusulkan demi peningkatan pelayanan dan kesejahteraan masyarakat;</w:t></w:r>
    </w:p>
    <w:p>
      <w:pPr>
        <w:jc w:val="both"/>
        <w:spacing w:before="0" w:after="80" w:line="276" w:lineRule="auto"/>
        <w:ind w:left="567" w:hanging="400"/>
      </w:pPr>
      <w:r><w:t>2. </w:t></w:r>
      <w:r><w:t>Sehubungan dengan kesiapan teknis dan kelengkapan administrasi, dimohon kerja sama Saudara untuk mengoordinasikan jadwal peninjauan lapangan bersama tim teknis perangkat daerah terkait;</w:t></w:r>
    </w:p>
    <w:p>
      <w:pPr>
        <w:jc w:val="both"/>
        <w:spacing w:before="0" w:after="140" w:line="276" w:lineRule="auto"/>
        <w:ind w:left="567" w:hanging="400"/>
      </w:pPr>
      <w:r><w:t>3. </w:t></w:r>
      <w:r><w:t>Hal-hal teknis mengenai tindak lanjut operasional dapat dikonsultasikan secara berkala melalui narahubung Bagian Umum Sekretariat Daerah Kota Baubau.</w:t></w:r>
    </w:p>

    <!-- PARAGRAF PENUTUP -->
    <w:p>
      <w:pPr>
        <w:jc w:val="both"/>
        <w:spacing w:before="0" w:after="200" w:line="276" w:lineRule="auto"/>
        <w:ind w:firstLine="567"/>
      </w:pPr>
      <w:r>
        <w:t>Demikian surat ini disampaikan untuk menjadi maklum dan dipergunakan sebagaimana mestinya. Atas perhatian dan kerja samanya, diucapkan terima kasih.</w:t>
      </w:r>
    </w:p>

    <!-- BLOK TANDA TANGAN (DI BAWAH KANAN) -->
    <!-- Menggunakan tabel 2 kolom: Kiri 5000 dxa (kosong), Kanan 4600 dxa (Blok Tanda Tangan) -->
    <w:tbl>
      <w:tblPr>
        <w:tblW w:w="9600" w:type="dxa"/>
        <w:tblBorders>
          <w:top w:val="none"/>
          <w:left w:val="none"/>
          <w:bottom w:val="none"/>
          <w:right w:val="none"/>
          <w:insideH w:val="none"/>
          <w:insideV w:val="none"/>
        </w:tblBorders>
      </w:tblPr>
      <w:tblGrid>
        <w:gridCol w:w="5000"/>
        <w:gridCol w:w="4600"/>
      </w:tblGrid>
      <w:tr>
        <!-- Kolom Kiri: Kosong (Area Aman) -->
        <w:tc>
          <w:p><w:pPr><w:spacing w:before="0" w:after="0"/></w:pPr></w:p>
        </w:tc>
        <!-- Kolom Kanan: Kuadran Kanan Bawah (Area Tanda Tangan & QR Code) -->
        <w:tc>
          <w:p>
            <w:pPr>
              <w:spacing w:before="0" w:after="20" w:line="240" w:lineRule="auto"/>
            </w:pPr>
            <w:r>
              <w:b/>
              <w:t>KEPALA BAGIAN UMUM,</w:t>
            </w:r>
          </w:p>
          <!-- Ruang Tanda Tangan Digital / QR Code (X: 0.72, Y: 0.82) -->
          <w:p>
            <w:pPr>
              <w:spacing w:before="0" w:after="0" w:line="240" w:lineRule="auto"/>
            </w:pPr>
          </w:p>
          <w:p>
            <w:pPr>
              <w:spacing w:before="0" w:after="0" w:line="240" w:lineRule="auto"/>
            </w:pPr>
          </w:p>
          <w:p>
            <w:pPr>
              <w:spacing w:before="0" w:after="0" w:line="240" w:lineRule="auto"/>
            </w:pPr>
            <w:r>
              <w:rPr>
                <w:color w:val="999999"/>
                <w:sz w:val="18"/>
                <w:szCs w:val="18"/>
                <w:i/>
              </w:rPr>
              <w:t>[ Ruang Tanda Tangan / QR Code ]</w:t>
            </w:r>
          </w:p>
          <w:p>
            <w:pPr>
              <w:spacing w:before="0" w:after="0" w:line="240" w:lineRule="auto"/>
            </w:pPr>
          </w:p>
          <!-- Nama Pejabat Penandatangan -->
          <w:p>
            <w:pPr>
              <w:spacing w:before="60" w:after="20" w:line="240" w:lineRule="auto"/>
            </w:pPr>
            <w:r>
              <w:rPr>
                <w:b/>
                <w:u w:val="single"/>
              </w:rPr>
              <w:t>[ NAMA LENGKAP KEPALA BAGIAN ]</w:t>
            </w:r>
          </w:p>
          <!-- Pangkat / Golongan -->
          <w:p>
            <w:pPr>
              <w:spacing w:before="0" w:after="20" w:line="240" w:lineRule="auto"/>
            </w:pPr>
            <w:r>
              <w:t>Pembina Tingkat I (IV/b)</w:t>
            </w:r>
          </w:p>
          <!-- NIP -->
          <w:p>
            <w:pPr>
              <w:spacing w:before="0" w:after="0" w:line="240" w:lineRule="auto"/>
            </w:pPr>
            <w:r>
              <w:t>NIP. 19780101 200312 1 002</w:t>
            </w:r>
          </w:p>
        </w:tc>
      </w:tr>
    </w:tbl>

    <!-- TEMBUSAN (POJOK KIRI BAWAH) -->
    <w:p>
      <w:pPr>
        <w:spacing w:before="240" w:after="40"/>
      </w:pPr>
      <w:r>
        <w:rPr><w:sz w:val="18"/><w:szCs w:val="18"/><w:u w:val="single"/></w:rPr>
        <w:t>Tembusan Yth:</w:t>
      </w:r>
    </w:p>
    <w:p>
      <w:pPr>
        <w:spacing w:before="0" w:after="20" w:line="220" w:lineRule="auto"/>
        <w:ind w:left="200"/>
      </w:pPr>
      <w:r>
        <w:rPr><w:sz w:val="18"/><w:szCs w:val="18"/></w:rPr>
        <w:t>1. Wali Kota Baubau (sebagai laporan);</w:t>
      </w:r>
    </w:p>
    <w:p>
      <w:pPr>
        <w:spacing w:before="0" w:after="20" w:line="220" w:lineRule="auto"/>
        <w:ind w:left="200"/>
      </w:pPr>
      <w:r>
        <w:rPr><w:sz w:val="18"/><w:szCs w:val="18"/></w:rPr>
        <w:t>2. Sekretaris Daerah Kota Baubau;</w:t>
      </w:r>
    </w:p>
    <w:p>
      <w:pPr>
        <w:spacing w:before="0" w:after="20" w:line="220" w:lineRule="auto"/>
        <w:ind w:left="200"/>
      </w:pPr>
      <w:r>
        <w:rPr><w:sz w:val="18"/><w:szCs w:val="18"/></w:rPr>
        <w:t>3. Asisten Administrasi Umum Setda Kota Baubau;</w:t>
      </w:r>
    </w:p>
    <w:p>
      <w:pPr>
        <w:spacing w:before="0" w:after="0" w:line="220" w:lineRule="auto"/>
        <w:ind w:left="200"/>
      </w:pPr>
      <w:r>
        <w:rPr><w:sz w:val="18"/><w:szCs w:val="18"/></w:rPr>
        <w:t>4. Pertinggal / Arsip.</w:t>
      </w:r>
    </w:p>

    <!-- SECTION PROPERTIES (MARGIN A4 STANDAR KEDINASAN) -->
    <w:sectPr>
      <w:pgSz w:w="11906" w:h="16838"/> <!-- A4 Size in twips -->
      <w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="720" w:footer="720" w:gutter="0"/>
    </w:sectPr>
  </w:body>
</w:document>"""

    # Create the zip archive
    with zipfile.ZipFile(output_path, 'w', compression=zipfile.ZIP_DEFLATED) as docx:
        docx.writestr('[Content_Types].xml', content_types_xml)
        docx.writestr('_rels/.rels', root_rels_xml)
        docx.writestr('word/_rels/document.xml.rels', doc_rels_xml)
        docx.writestr('word/styles.xml', styles_xml)
        docx.writestr('word/document.xml', document_xml)

    print(f"Successfully generated DOCX at {output_path} (Size: {os.path.getsize(output_path)} bytes)")

if __name__ == '__main__':
    targets = [
        'storage/app/templates/template-surat-dinas-bagian.docx',
        'public/templates/template-surat-dinas-bagian.docx',
        r'C:/Users/laode/.gemini/antigravity/brain/26e7842d-381d-4ca6-b478-93b52c8b25ae/template-surat-dinas-bagian.docx',
    ]
    for target in targets:
        generate_letter_template(target)
