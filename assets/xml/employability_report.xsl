<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:output method="html" encoding="UTF-8" indent="yes"/>

  <xsl:template match="/">
    <html>
      <head>
        <meta charset="UTF-8"/>
        <title>PLP Employability XML Report</title>
        <style>
          body { font-family: Arial, sans-serif; margin: 20px; background: #f8fafc; color: #111827; }
          .wrap { max-width: 1200px; margin: 0 auto; }
          .card { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; margin-bottom: 16px; }
          .title { margin: 0; font-size: 24px; color: #0d5c34; }
          .sub { margin: 8px 0 0 0; color: #6b7280; font-size: 13px; }
          .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
          .kpi { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; }
          .kpi .label { font-size: 12px; color: #6b7280; margin-bottom: 6px; }
          .kpi .value { font-size: 20px; font-weight: 700; color: #111827; }
          table { width: 100%; border-collapse: collapse; font-size: 13px; }
          th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; vertical-align: top; }
          th { background: #f3f4f6; font-weight: 700; }
          .section-title { margin: 0 0 12px 0; font-size: 18px; color: #1f2937; }
          .muted { color: #6b7280; font-size: 12px; }
          @media (max-width: 1024px) {
            .grid { grid-template-columns: repeat(2, 1fr); }
          }
        </style>
      </head>
      <body>
        <div class="wrap">
          <div class="card">
            <h1 class="title">PLP Alumni Tracer - Employability Report</h1>
            <p class="sub">
              Generated at:
              <xsl:value-of select="employabilityReport/@generatedAtUtc"/>
              |
              Version:
              <xsl:value-of select="employabilityReport/@version"/>
            </p>
          </div>

          <div class="card">
            <h2 class="section-title">Summary</h2>
            <div class="grid">
              <div class="kpi">
                <div class="label">Total Assessments</div>
                <div class="value"><xsl:value-of select="employabilityReport/summary/totalAssessments"/></div>
              </div>
              <div class="kpi">
                <div class="label">Employment Rate</div>
                <div class="value"><xsl:value-of select="employabilityReport/summary/employmentRatePercent"/>%</div>
              </div>
              <div class="kpi">
                <div class="label">Good Match Count</div>
                <div class="value"><xsl:value-of select="employabilityReport/summary/goodMatchCount"/></div>
              </div>
              <div class="kpi">
                <div class="label">Good Match Rate</div>
                <div class="value"><xsl:value-of select="employabilityReport/summary/goodMatchRatePercent"/>%</div>
              </div>
              <div class="kpi">
                <div class="label">Total Employed</div>
                <div class="value"><xsl:value-of select="employabilityReport/summary/totalEmployed"/></div>
              </div>
              <div class="kpi">
                <div class="label">Total Unemployed</div>
                <div class="value"><xsl:value-of select="employabilityReport/summary/totalUnemployed"/></div>
              </div>
              <div class="kpi">
                <div class="label">Job Mismatch Count</div>
                <div class="value"><xsl:value-of select="employabilityReport/summary/jobMismatchCount"/></div>
              </div>
              <div class="kpi">
                <div class="label">System</div>
                <div class="value"><xsl:value-of select="employabilityReport/@system"/></div>
              </div>
            </div>
          </div>

          <div class="card">
            <h2 class="section-title">Program Breakdown</h2>
            <table>
              <thead>
                <tr>
                  <th>Program</th>
                  <th>Total</th>
                  <th>Employed</th>
                  <th>Employment %</th>
                  <th>Good Match</th>
                  <th>Good Match %</th>
                  <th>Avg GPA</th>
                  <th>Avg OJT</th>
                  <th>Avg Soft Skills</th>
                  <th>Avg Hard Skills</th>
                </tr>
              </thead>
              <tbody>
                <xsl:for-each select="employabilityReport/programBreakdown/program">
                  <tr>
                    <td><xsl:value-of select="name"/></td>
                    <td><xsl:value-of select="totalAssessments"/></td>
                    <td><xsl:value-of select="employedCount"/></td>
                    <td><xsl:value-of select="employmentRatePercent"/>%</td>
                    <td><xsl:value-of select="goodMatchCount"/></td>
                    <td><xsl:value-of select="goodMatchRatePercent"/>%</td>
                    <td><xsl:value-of select="averageGpa"/></td>
                    <td><xsl:value-of select="averageOjtGrade"/></td>
                    <td><xsl:value-of select="averageSoftSkills"/></td>
                    <td><xsl:value-of select="averageHardSkills"/></td>
                  </tr>
                </xsl:for-each>
              </tbody>
            </table>
          </div>

          <div class="card">
            <h2 class="section-title">Recent Predictions (Latest 50)</h2>
            <p class="muted">This section is generated directly from the XML payload.</p>
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Program</th>
                  <th>Grad Year</th>
                  <th>Employment Status</th>
                  <th>Employability Status</th>
                  <th>Recommended Profession</th>
                  <th>GPA</th>
                  <th>OJT</th>
                  <th>Soft Avg</th>
                  <th>Hard Avg</th>
                  <th>Created At</th>
                </tr>
              </thead>
              <tbody>
                <xsl:for-each select="employabilityReport/recentPredictions/prediction">
                  <tr>
                    <td><xsl:value-of select="@assessmentId"/></td>
                    <td><xsl:value-of select="name"/></td>
                    <td><xsl:value-of select="program"/></td>
                    <td><xsl:value-of select="graduationYear"/></td>
                    <td><xsl:value-of select="employmentStatus"/></td>
                    <td><xsl:value-of select="employabilityStatus"/></td>
                    <td><xsl:value-of select="recommendedProfession"/></td>
                    <td><xsl:value-of select="gpa"/></td>
                    <td><xsl:value-of select="ojtGrade"/></td>
                    <td><xsl:value-of select="softSkillsAverage"/></td>
                    <td><xsl:value-of select="hardSkillsAverage"/></td>
                    <td><xsl:value-of select="createdAt"/></td>
                  </tr>
                </xsl:for-each>
              </tbody>
            </table>
          </div>
        </div>
      </body>
    </html>
  </xsl:template>
</xsl:stylesheet>
